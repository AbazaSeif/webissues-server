<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2011 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

if ( !defined( 'WI_VERSION' ) ) die( -1 );

/**
* Manage projects and folders.
*
* Like all API classes, this class does not check permissions to perform
* an operation and does not validate the input values. An error is thrown
* only if the requested object does not exist or is inaccessible.
*/
class System_Api_ProjectManager extends System_Api_Base
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Administrator access is required for the project or folder. */
    const RequireAdministrator = 1;
    /** Force deletion with entire contents. */
    const ForceDelete = 2;
    /*@}*/

    private static $folders = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get list of accessible projects.
    * @return An array of associative arrays representing project.
    */
    public function getProjects()
    {
        $principal = System_Api_Principal::getCurrent();

        if ( !$principal->isAdministrator() ) {
            $query = 'SELECT p.project_id, p.project_name, r.project_access FROM {projects} AS p'
                . ' JOIN {rights} AS r ON r.project_id = p.project_id AND r.user_id = %1d';
        } else {
            $query = 'SELECT p.project_id, p.project_name, %2d AS project_access FROM {projects} AS p';
        }
        $query .= ' ORDER BY p.project_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess );
    }

    /**
    * Get the project with given identifier.
    * @param $projectId Identifier of the project.
    * @param $flags If RequireAdministrator is passed an error is thrown
    * if the user does not have administrator access to
    * the project.
    * @return Array containing project details.
    */
    public function getProject( $projectId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( !$principal->isAdministrator() ) {
            $query = 'SELECT p.project_id, p.project_name, r.project_access FROM {projects} AS p'
                . ' JOIN {rights} AS r ON r.project_id = p.project_id AND r.user_id = %2d'
                . ' WHERE p.project_id = %1d';
        } else {
            $query = 'SELECT project_id, project_name, %3d AS project_access FROM {projects} WHERE project_id = %1d';
        }

        if ( !( $project = $this->connection->queryRow( $query, $projectId, $principal->getUserId(), System_Const::AdministratorAccess ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownProject );

        if ( $flags & self::RequireAdministrator && $project[ 'project_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $project;
    }

    /**
    * Get list of folders in all accessible projects.
    * @return An array of associative arrays representing folders.
    */
    public function getFolders()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT f.folder_id, f.project_id, f.folder_name, f.type_id, f.stamp_id, t.type_name FROM {folders} AS f';
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {rights} AS r ON r.project_id = f.project_id AND r.user_id = %d';
        $query .= ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
            . ' ORDER BY f.folder_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId() );
    }

    /**
    * Get the project with given identifier. Information about the related
    * project is also returned. Folders are cached to prevent accessing
    * the database unnecessarily.
    * @param $folderId Identifier of the folder.
    * @param $flags If RequireAdministrator is passed an error is thrown
    * if the user does not have administrator access to the project containing
    * the folder.
    * @return Array containing project details.
    */
    public function getFolder( $folderId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( isset( self::$folders[ $folderId ] ) ) {
            $folder = self::$folders[ $folderId ];
        } else {
            $query = 'SELECT f.folder_id, f.folder_name, f.type_id, f.stamp_id, p.project_id, p.project_name, t.type_name,';
            if ( !$principal->isAdministrator() )
                $query .= ' r.project_access';
            else
                $query .= ' %3d AS project_access';
            $query .= ' FROM {folders} AS f'
                . ' JOIN {projects} AS p ON p.project_id = f.project_id'
                . ' JOIN {issue_types} AS t ON t.type_id = f.type_id';
            if ( !$principal->isAdministrator() )
                $query .= ' JOIN {rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
            $query .= ' WHERE f.folder_id = %1d';

            if ( !( $folder = $this->connection->queryRow( $query, $folderId, $principal->getUserId(), System_Const::AdministratorAccess ) ) )
                throw new System_Api_Error( System_Api_Error::UnknownFolder );

            self::$folders[ $folderId ] = $folder;
        }

        if ( $flags & self::RequireAdministrator && $folder[ 'project_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $folder;
    }

    public function getFolderFromIssue( $issue )
    {
        $folder = array();
        $folder[ 'folder_id' ] = $issue[ 'folder_id' ];
        $folder[ 'folder_name' ] = $issue[ 'folder_name' ];
        $folder[ 'type_id' ] = $issue[ 'type_id' ];
        $folder[ 'type_name' ] = $issue[ 'type_name' ];
        $folder[ 'project_id' ] = $issue[ 'project_id' ];
        $folder[ 'project_name' ] = $issue[ 'project_name' ];
        $folder[ 'project_access' ] = $issue[ 'project_access' ];
        return $folder;
    }

    /**
    * Create a new project. An error is thrown if a project with given name
    * already exists.
    * @param $name The name of the project to create.
    * @return The identifier of the new project.
    */
    public function addProject( $name )
    {
        $query = 'SELECT project_id FROM {projects} WHERE project_name = %s';
        if ( $this->connection->queryScalar( $query, $name ) !== false )
            throw new System_Api_Error( System_Api_Error::ProjectAlreadyExists );

        $query = 'INSERT INTO {projects} ( project_name ) VALUES ( %s )';
        $this->connection->execute( $query, $name );

        return $this->connection->getInsertId( 'projects', 'project_id' );
    }

    /**
    * Rename a project. An error is thrown if another project with given name
    * already exists.
    * @param $project The project to rename.
    * @param $newName The new name of the project.
    * @return @c true if the name was modified.
    */
    public function renameProject( $project, $newName )
    {
        $projectId = $project[ 'project_id' ];
        $oldName = $project[ 'project_name' ];

        if ( $newName == $oldName )
            return false;

        $query = 'SELECT project_id FROM {projects} WHERE project_name = %s';
        if ( $this->connection->queryScalar( $query, $newName ) !== false )
            throw new System_Api_Error( System_Api_Error::ProjectAlreadyExists );

        $query = 'UPDATE {projects} SET project_name = %s WHERE project_id = %d';
        $this->connection->execute( $query, $newName, $projectId );

        return true;
    }

    /**
    * Delete a project.
    * @param $project The project to delete.
    * @param $flags If ForceDelete is passed the project is deleted
    * even if it contains folders. Otherwise an error is thrown.
    * @return @c true if the project was deleted.
    */
    public function deleteProject( $project, $flags = 0 )
    {
        $projectId = $project[ 'project_id' ];

        if ( !( $flags & self::ForceDelete ) && $this->checkProjectNotEmpty( $project ) )
            throw new System_Api_Error( System_Api_Error::CannotDeleteProject );

        $query = 'SELECT fl.file_id FROM {files} AS fl'
            . ' JOIN {changes} ch ON ch.change_id = fl.file_id'
            . ' JOIN {issues} i ON i.issue_id = ch.issue_id'
            . ' JOIN {folders} f ON f.folder_id = i.folder_id'
            . ' WHERE f.project_id = %d AND fl.file_storage = %d';
        $files = $this->connection->queryTable( $query, $projectId, System_Api_IssueManager::FileSystemStorage );

        $query = 'DELETE FROM {projects} WHERE project_id = %d';
        $this->connection->execute( $query, $projectId );

        $issueManager = new System_Api_IssueManager();
        $issueManager->deleteFiles( $files );

        return true;
    }

    /**
    * Check if the project is not empty.
    * @return @c true if the project contains folders.
    */
    public function checkProjectNotEmpty( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT COUNT(*) FROM {folders} WHERE project_id = %d';

        return $this->connection->queryScalar( $query, $projectId ) > 0;
    }

    /**
    * Create a new folder in the given project. An error is thrown if a folder
    * with given name already exists in the project.
    * @param $project The project where the new folder is located.
    * @param $type The type of issues stored in the new folder.
    * @param $name The name of the folder to create.
    * @return The identifier of the new folder.
    */
    public function addFolder( $project, $type, $name )
    {
        $projectId = $project[ 'project_id' ];
        $typeId = $type[ 'type_id' ];

        $query = 'SELECT folder_id FROM {folders} WHERE project_id = %d AND folder_name = %s';
        if ( $this->connection->queryScalar( $query, $projectId, $name ) !== false )
            throw new System_Api_Error( System_Api_Error::FolderAlreadyExists );

        $query = 'INSERT INTO {folders} ( project_id, type_id, folder_name ) VALUES ( %d, %d, %s )';
        $this->connection->execute( $query, $projectId, $typeId, $name );

        return $this->connection->getInsertId( 'folders', 'folder_id' );
    }

    /**
    * Rename a folder. An error is thrown if another folder with given name
    * already exists in the project.
    * @param $folder The folder to rename.
    * @param $newName The new name of the folder.
    * @return @c true if the name was modified.
    */
    public function renameFolder( $folder, $newName )
    {
        $folderId = $folder[ 'folder_id' ];
        $projectId = $folder[ 'project_id' ];
        $oldName = $folder[ 'folder_name' ];

        if ( $newName == $oldName )
            return false;

        $query = 'SELECT folder_id FROM {folders} WHERE project_id = %d AND folder_name = %s';
        if ( $this->connection->queryScalar( $query, $projectId, $newName ) !== false )
            throw new System_Api_Error( System_Api_Error::FolderAlreadyExists );

        $query = 'UPDATE {folders} SET folder_name = %s WHERE folder_id = %d';
        $this->connection->execute( $query, $newName, $folderId );

        return true;
    }

    /**
    * Delete a folder.
    * @param $folder The folder to delete.
    * @param $flags If ForceDelete is passed the folder is deleted
    * even if it contains issues. Otherwise an error is thrown.
    * @return @c true if the folder was deleted.
    */
    public function deleteFolder( $folder, $flags = 0 )
    {
        $folderId = $folder[ 'folder_id' ];

        if ( !( $flags & self::ForceDelete ) && $this->checkFolderNotEmpty( $folder ) )
            throw new System_Api_Error( System_Api_Error::CannotDeleteFolder );

        $query = 'SELECT fl.file_id FROM {files} AS fl'
            . ' JOIN {changes} ch ON ch.change_id = fl.file_id'
            . ' JOIN {issues} i ON i.issue_id = ch.issue_id'
            . ' WHERE i.folder_id = %d AND fl.file_storage = %d';
        $files = $this->connection->queryTable( $query, $folderId, System_Api_IssueManager::FileSystemStorage );

        $query = 'DELETE FROM {folders} WHERE folder_id = %d';
        $this->connection->execute( $query, $folderId );

        $issueManager = new System_Api_IssueManager();
        $issueManager->deleteFiles( $files );

        return true;
    }

    /**
    * Check if the folder is not empty.
    * @return @c true if the folder contains issues.
    */
    public function checkFolderNotEmpty( $folder )
    {
        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT COUNT(*) FROM {issues} WHERE folder_id = %d';

        return $this->connection->queryScalar( $query, $folderId ) > 0;
    }

    /**
    * Move a folder to another project.
    * @param $folder The folder to move.
    * @param $project The target project.
    * @return @c true if the foler was moved.
    */
    public function moveFolder( $folder, $project )
    {
        $folderId = $folder[ 'folder_id' ];
        $fromProjectId = $folder[ 'project_id' ];
        $name = $folder[ 'folder_name' ];

        $toProjectId = $project[ 'project_id' ];

        if ( $fromProjectId == $toProjectId )
            return false;

        $query = 'SELECT folder_id FROM {folders} WHERE project_id = %d AND folder_name = %s';
        if ( $this->connection->queryScalar( $query, $toProjectId, $name ) !== false )
            throw new System_Api_Error( System_Api_Error::FolderAlreadyExists );

        $query = 'UPDATE {folders} SET project_id = %d WHERE folder_id = %d';
        $this->connection->execute( $query, $toProjectId, $folderId );

        return true;
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getProjectsColumns()
    {
        return array( 'name' => 'p.project_name COLLATE LOCALE' );
    }

    /**
    * Return the total number of accessible projects.
    * @return The number of projects.
    */
    public function getProjectsCount()
    {
        $principal = System_Api_Principal::getCurrent();
        if ( !$principal->isAdministrator() )
            $query = 'SELECT COUNT(*) FROM {projects} AS p'
                . ' JOIN {rights} AS r ON r.project_id = p.project_id AND r.user_id = %d';
        else
            $query = 'SELECT COUNT(*) FROM {projects} AS p';
 
        return $this->connection->queryScalar( $query, $principal->getUserId() );
    }

    /**
    * Get paged list of accessible projects.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing projects.
    */
    public function getProjectsPage( $orderBy, $limit, $offset )
    {
        $principal = System_Api_Principal::getCurrent();
        if ( !$principal->isAdministrator() )
            $query = 'SELECT p.project_id, p.project_name, r.project_access, r.user_id FROM {projects} AS p'
                . ' JOIN {rights} AS r ON r.project_id = p.project_id AND r.user_id = %d';
        else
            $query = 'SELECT p.project_id, p.project_name FROM {projects} AS p';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $principal->getUserId() );
    }

    /**
    * Get list of folders in given project.
    * @param $projectId Identifier of the project.
    * @return An array of associative arrays representing folders.
    */
    public function getFoldersForProject( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT f.folder_id, f.folder_name, t.type_name FROM {folders} AS f'
            . ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
            . ' WHERE f.project_id = %d'
            . ' ORDER BY f.folder_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $projectId );
    }
}
