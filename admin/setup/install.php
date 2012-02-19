<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2012 WebIssues Team
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

require_once( '../../system/bootstrap.inc.php' );

class Admin_Setup_Install extends System_Web_Component
{
    private $rules = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        if ( $this->checkAccess() ) {
            $locale = new System_Api_Locale();
            $this->languageOptions = $locale->getAvailableLanguages();
            $this->engineOptions = $this->getDatabaseEngines();
            $this->dataOptions = $this->getDataOptions();

            $this->form = new System_Web_Form( 'install', $this );
            $this->form->addViewState( 'page', 'language' );
            $this->form->addPersistentField( 'language', 'en_US' );
            $this->form->addPersistentField( 'engine', key( $this->engineOptions ) );
            $this->form->addPersistentField( 'host', 'localhost' );
            $this->form->addPersistentField( 'database', 'webissues' );
            $this->form->addPersistentField( 'user', 'webissues' );
            $this->form->addPersistentField( 'password' );
            $this->form->addPersistentField( 'prefix' );
            $this->form->addPersistentField( 'serverName' );
            $this->form->addPersistentField( 'adminPassword' );
            $this->form->addPersistentField( 'adminConfirm' );
            $this->form->addPersistentField( 'initialData', 'default' );
            $this->form->addPersistentField( 'prefix085' );

            if ( $this->form->loadForm() )
                $this->processForm();

            switch ( $this->page ) {
                case 'site':
                    $this->validateSite();
                    break;

                case 'connection':
                    $this->validateConnection();
                    break;
            }

            $this->showRefresh = $this->disableNext || $this->disableInstall;
            $this->showInstall = ( $this->page == 'new_site' || $this->page == 'existing_site' );
            $this->showBack = ( $this->page != 'language' );
            $this->showNext = !$this->showInstall;

            $this->initializeRules();
        }

        $this->view->setDecoratorClass( 'Common_FixedBlock' );
        $this->view->setSlot( 'page_title', $this->tr( 'Server Configuration' ) );
        $this->view->setSlot( 'header', $this->tr( 'Configure your WebIssues Server' ) );

        if ( $this->showInstall ) {
            $javaScript = new System_Web_JavaScript( $this->view );
            $javaScript->registerBlockUI( $this->form->getSubmitSelector( 'install' ), '#progress' );
        }
    }

    private function checkAccess()
    {
        if ( System_Core_Application::getInstance()->getSite()->isConfigLoaded() ) {
            $this->page = 'config_exists';
            return false;
        }

        return true;
    }

    private function processForm()
    {
        $this->initializeRules();
        $this->form->validate();

        if ( !$this->setupLanguage() ) {
            $this->page = 'language';
            return;
        }

        if ( $this->form->isSubmittedWith( 'back' ) ) {
            switch ( $this->page ) {
                case 'site':
                    $this->page = 'language';
                    break;
                case 'connection':
                    $this->page = 'site';
                    break;
                case 'server':
                case 'existing_site':
                    $this->page = 'connection';
                    break;
                case 'new_site':
                    $this->page = 'server';
                    break;
            }
        }

        if ( $this->form->isSubmittedWith( 'next' ) && !$this->form->hasErrors() ) {
            switch ( $this->page ) {
                case 'language':
                    $this->page = 'site';
                    break;
                case 'site':
                    $this->page = 'connection';
                    break;
                case 'connection':
                    if ( $this->openConnection() )
                        $this->testConnection();
                    break;
                case 'server':
                    if ( $this->openConnection() )
                        $this->testImport();
                    break;
            }
        }

        if ( $this->form->isSubmittedWith( 'install' ) && !$this->form->hasErrors() ) {
            switch ( $this->page ) {
                case 'new_site':
                    if ( $this->openConnection() ) {
                        if ( $this->installDatabase() ) {
                            if ( $this->writeSiteConfiguration() ) {
                                $this->startSession();
                                $this->page = 'completed';
                            }
                        }
                    }
                    break;
                case 'existing_site':
                    if ( $this->openConnection() ) {
                        if ( $this->writeSiteConfiguration() ) {
                            $this->startSession();
                            $this->page = 'completed';
                        }
                    }
                    break;
            }
        }
    }

    private function initializeRules()
    {
        if ( $this->rules == $this->page )
            return;

        $this->rules = $this->page;

        $this->form->clearRules();

        switch ( $this->page ) {
            case 'language':
                $this->form->addItemsRule( 'language', $this->languageOptions );
                break;

            case 'connection':
                $this->form->addItemsRule( 'engine', $this->engineOptions );
                $this->form->addTextRule( 'host', System_Const::NameMaxLength );
                $this->form->addTextRule( 'database', System_Const::NameMaxLength );
                $this->form->addTextRule( 'user', System_Const::LoginMaxLength, System_Api_Parser::AllowEmpty );
                $this->form->addTextRule( 'password', System_Const::PasswordMaxLength, System_Api_Parser::AllowEmpty );
                $this->form->addTextRule( 'prefix', System_Const::NameMaxLength, System_Api_Parser::AllowEmpty );
                break;

            case 'server':
                $this->form->addTextRule( 'serverName', System_Const::NameMaxLength );
                $this->form->addTextRule( 'adminPassword', System_Const::PasswordMaxLength );
                $this->form->addTextRule( 'adminConfirm', System_Const::PasswordMaxLength );
                $this->form->addPasswordRule( 'adminConfirm', 'adminPassword' );
                $this->form->addItemsRule( 'initialData', $this->dataOptions );
                break;
        }
    }

    private function setupLanguage()
    {
        if ( !empty( $this->language ) && isset( $this->languageOptions[ $this->language ] ) ) {
            $translator = System_Core_Application::getInstance()->getTranslator();
            $translator->addModule( 'webissues' );
            $translator->setLanguage( System_Core_Translator::SystemLanguage, $this->language );
            $translator->setLanguage( System_Core_Translator::UserLanguage, $this->language );

            if ( $this->serverName === null )
                $this->serverName = $this->tr( 'My WebIssues Server' );

            $this->dataOptions = $this->getDataOptions();

            return true;
        }

        return false;
    }

    private function validateSite()
    {
        $siteComponent = System_Web_Component::createComponent( 'Admin_Info_Site', null, $this->form, $this->view );

        $this->site = new System_Web_RawValue( $siteComponent->run() );

        if ( $this->form->hasErrors() )
            $this->disableNext = true;
    }

    private function validateConnection()
    {
        if ( empty( $this->engineOptions ) ) {
            $this->form->setError( 'engine', $this->tr( 'No supported database engines are available in this PHP installation.' ) );
            $this->disableNext = true;
        }
    }

    private function getDatabaseEngines()
    {
        $engines = array();

        if ( function_exists( 'mysqli_connect' ) )
            $engines[ 'mysqli' ] = 'MySQL';

        if ( function_exists( 'pg_connect' ) )
            $engines[ 'pgsql' ] = 'PostgreSQL';

        if ( @class_exists( 'COM', false ) )
            $engines[ 'mssql' ] = 'SQL Server';

        return $engines;
    }

    private function getDataOptions()
    {
        $options = array();

        $options[ '' ] = $this->tr( 'Do not install any issue types' );
        $options[ 'default' ] = $this->tr( 'Install the default set of issue types' );
        $options[ 'import' ] = $this->tr( 'Import data from WebIssues Server 0.8.5' );

        return $options;
    }

    private function openConnection()
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            $connection->loadEngine( $this->engine );
            $connection->open( $this->host, $this->database, $this->user, $this->password );
            $connection->setPrefix( $this->prefix );

            return true;
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'connection';
            $this->form->setError( 'connection', $this->tr( 'Could not connect to database. Please check connection details and try again.' ) );

            return false;
        }
    }

    private function testConnection()
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            if ( $this->checkPrerequisites() ) {
                if ( !$connection->checkTableExists( 'server' ) ) {
                    $this->page = 'server';
                } else {
                    $serverManager = new System_Api_ServerManager();
                    $this->server = $serverManager->getServer();

                    if ( $this->server[ 'db_version' ] == WI_DATABASE_VERSION ) {
                        $this->page = 'existing_site';
                    } else {
                        $this->form->setError( 'connection', $this->tr( 'The existing version of the database cannot be used with this version of WebIssues Server.' ) );
                    }
                }
            }
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'connection';
            $this->form->setError( 'connection', $this->tr( 'Could not retrieve information from the database.' ) );
        }
    }

    private function testImport()
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            if ( $this->initialData == 'import' ) {
                $connection->setPrefix( $this->prefix085 );

                if ( !$connection->checkTableExists( 'server' ) ) {
                    $this->form->setError( 'prefix085', $this->tr( 'No data tables were found in the database.' ) );
                } else {
                    $serverManager = new System_Api_ServerManager();
                    $this->server = $serverManager->getServer();

                    if ( $this->server[ 'db_version' ] == '0.8.5' ) {
                        $this->page = 'new_site';
                    } else {
                        $this->form->setError( 'prefix085', $this->tr( 'The existing version of the database cannot be imported.' ) );
                    }
                }
            } else {
                $this->page = 'new_site';
            }
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'connection';
            $this->form->setError( 'connection', $this->tr( 'Could not retrieve information from the database.' ) );
        }
    }

    private function checkPrerequisites()
    {
        switch ( $this->engine ) {
            case 'mysqli':
                if ( !$this->checkDatabaseVersion( '5.0.15' ) )
                    return false;

                $connection = System_Core_Application::getInstance()->getConnection();

                if ( !$connection->getParameter( 'have_innodb' ) ) {
                    $this->form->setError( 'connection', $this->tr( 'Database does not support InnoDB storage which is required by WebIssues Server.' ) );
                    return false;
                }
                break;

            case 'pgsql':
                if ( !$this->checkDatabaseVersion( '8.0' ) )
                    return false;
                break;

            case 'mssql':
                if ( !$this->checkDatabaseVersion( '09.00.1399' ) )
                    return false;
                break;
        }

        return true;
    }

    private function checkDatabaseVersion( $minVersion )
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        $version = $connection->getParameter( 'version' );

        if ( version_compare( $version, $minVersion ) < 0 ) {
            $this->form->setError( 'connection', $this->tr( 'Database version %1 is older than minimum required version %2.', null, $version, $minVersion ) );
            return false;
        }

        return true;
    }

    private function installDatabase()
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            $installer = new Admin_Setup_Installer( $connection );

            $installer->installSchema();
            $installer->installData( $this->serverName, $this->adminPassword );

            switch ( $this->initialData ) {
                case 'default':
                    $installer->installDefaultTypes();
                    break;

                case 'import':
                    $installer->importData( $this->prefix085 );

                    $issueManager = new System_Api_IssueManager();
                    $this->hasFileSystemFiles = $issueManager->checkFileSystemFiles();
                    break;
            }

            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Completed the installation of the server' ) );

            return true;
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'failed';
            $this->error = $e->__toString();

            return false;
        }
    }

    private function writeSiteConfiguration()
    {
        foreach( array( 'engine', 'host', 'database', 'user', 'password', 'prefix' ) as $key )
            $values[ 'db_' . $key ] = $this->$key;

        $config = System_Web_Component::createComponent( 'Admin_Setup_Config', null, $values );
        $body = "<?php\n" . $config->run();

        $site = System_Core_Application::getInstance()->getSite();

        $siteDir = $site->getPath( 'site_dir' );
        $path = $siteDir . '/config.inc.php';

        if ( @file_put_contents( $path, $body, LOCK_EX ) === false ) {
            $this->error = $this->tr( 'The configuration file could not be written.' );
            $this->page = 'failed';
            return false;
        }

        return true;
    }

    private function startSession()
    {
        System_Core_Application::getInstance()->initializeServer();
        System_Core_Application::getInstance()->initializeSession();

        $sessionManager = new System_Api_SessionManager();
        $sessionManager->loginAs( 'admin' );
    }
}

System_Bootstrap::run( 'Common_Application', 'Admin_Setup_Install' );
