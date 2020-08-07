<?php

    class ReolinkCamera extends IPSModule {
 
        public function __construct($InstanceID) {
          /* Constructor is called before each function call */
          parent::__construct($InstanceID);
        }
        
        public function Create() {
          /* Create is called ONCE on Instance creation and start of IP-Symcon.
             Status-Variables und Modul-Properties for permanent usage should be created here  */
          parent::Create(); 
            
          // Properties of the Module
          $this->RegisterPropertyString("IPAddressDevice", "0.0.0.0" ); 
          $this->RegisterPropertyString("Username", "" );
          $this->RegisterPropertyString("Password", "" );
          $this->RegisterPropertyBoolean("AdminUser", false );
          $this->RegisterPropertyInteger("UpdateFrequency", 0);  
          $this->RegisterPropertyBoolean("DebugLog", false );
            
          // Timer
          $this->RegisterTimer("ReolinkCamera_UpdateTimer", 0, 'ReolinkCamera_Update($_IPS[\'TARGET\']);');
        }
 
        public function ApplyChanges() {
          /* Called on 'apply changes' in the configuration UI and after creation of the instance */
          parent::ApplyChanges(); 

          // Generate Profiles & Variables
          $this->registerProfiles();
          $this->registerVariables();  

		  // check configuration
		  $this->checkConfiguration();

          // Set Data to Variables (and update timer)
          // $this->Update();
        } 
        
        public function Destroy() {
            $this->UnregisterTimer("ReolinkCamera_UpdateTimer");
            // Never delete this line!
            parent::Destroy();
        }
        
        //=== Modul Funktionen =========================================================================================
        /* Own module functions called via the defined prefix ReolinkCamera_* 
        */
        
        public function Update() {
        	
            /* Check the connection to the go-eCharger */
            $reolinkCameraStatus = $this->getStatusFromCamera();
            if ( $reolinkCameraStatus == false ) { return false; }
       
            // write values into variables
            /* SetValue($this->GetIDForIdent("status"),                  $goEChargerStatus->{'car'});    */
                
            // Set Timer
            if ( $this->ReadPropertyInteger("UpdateFrequency") > 0 ) {
              $this->SetTimerInterval("ReolinkCamera_UpdateTimer", $this->ReadPropertyInteger("UpdateFrequency")*1000);
            } else { 
              $this->SetTimerInterval("ReolinkCamera_UpdateTimer", 0 );
            }

            return true;
        }
               
        //=== Modul Funktionen =========================================================================================
        /* Internal Functions
        */
        
        protected function checkConfiguration() {
            // get IP of Device from configuration
            $IPAddress = trim($this->ReadPropertyString("IPAddressDevice"));
            
            // check if IP is ocnfigured and valid
            if ( $IPAddress == "0.0.0.0" ) {
                $this->SetStatus(200); // no configuration done
                return false;
            } elseif (filter_var($IPAddress, FILTER_VALIDATE_IP) == false) { 
                $this->SetStatus(201); // no valid IP configured
                return false;
            }
            
            // check if any HTTP device on IP can be reached
            if ( $this->ping( $IPAddress, 80, 1 ) == false ) {
                $this->SetStatus(202); // no http response
                return false;
            }
            
            // check user name and login
            if ( ( trim($this->ReadPropertyString("Username")) == "" ) or ( trim($this->ReadPropertyString("Password")) == "" ) ) {
                $this->SetStatus(205); // Invalid or No User Data
                return false;   
            }

            // Try test login and gather user data
            $LoginToken = ReolinkLogin( trim($this->ReadPropertyString("Username")), trim($this->ReadPropertyString("Password")) );      
            if ( $LoginToken === false ) {
                $this->SetStatus(206); // No Login possible
            } else {
                // get user data
                
                // logout
                if ( ReolinkLogout( $LoginToken ) == false ) {
                    echo "Logout failed";
                    return false;
                }
            }
                   
            $this->SetStatus(102);
            return true;
        }
        
        protected function getStatusFromCamera() {
            // get IP of Device from configuration
            $IPAddress = trim($this->ReadPropertyString("IPAddressDevice"));
              
                          
            $this->SetStatus(102);
            return true;        }
        
       
        
        protected function ping($host, $port, $timeout) 
        { 
            ob_start();
            $fP = fSockOpen($host, $port, $errno, $errstr, $timeout); 
            ob_clean();
            if (!$fP) { return false; } 
            return true; 
        }
            
        protected function registerProfiles() {
            // Generate Variable Profiles
            /* if ( !IPS_VariableProfileExists('GOECHARGER_Status') ) {
                IPS_CreateVariableProfile('GOECHARGER_Status', 1 );
                IPS_SetVariableProfileIcon('GOECHARGER_Status', 'Ok' );
                IPS_SetVariableProfileAssociation("GOECHARGER_Status", 1, "Ladestation bereit, kein Fahrzeug"       , "", 0xFFFFFF);
                IPS_SetVariableProfileAssociation("GOECHARGER_Status", 2, "Fahrzeug lädt"                           , "", 0xFFFFFF);
                IPS_SetVariableProfileAssociation("GOECHARGER_Status", 3, "Warten auf Fahrzeug"                     , "", 0xFFFFFF);
                IPS_SetVariableProfileAssociation("GOECHARGER_Status", 4, "Ladung beendet, Fahrzeug noch verbunden" , "", 0xFFFFFF);
            }    */
        }
        
        protected function registerVariables() {
            
            //--- Basic Information -------------------------------------------------------------
            $this->RegisterVariableBoolean("motionDetected", "Bewegung","~Motion",11);
            
        }
        
        
        /*=== REOLINK NATIVE FUNCTIONS ============== */
        protected function ReolinkLogin( $username, $password ) {
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=Login" );
            $command["cmd"] = "Login";
            $command["param"]["User"]["userName"] = $username;
            $command["param"]["User"]["password"] = $password;
            $jsonParam = "[".json_encode( $command )."]";
            curl_setopt($ch, CURLOPT_POST, 1) ;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParam );
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec($ch); 
            $responseArray = json_decode( $response, true );
            curl_close( $ch );
            if (isset( $responseArray[0]["value"]["Token"]["name"] ) ) {
                $this->toDebugLog( "Token Received" );
                return $responseArray[0]["value"]["Token"]["name"];
            } else {
                $this->toDebugLog( "Login failed" );
                return false;
            }
        }

        protected function ReolinkLogout( $Token ) {
            $file = "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=Logout&token=".$Token;
            $response = file_get_contents( $file );
            $responseArray = json_decode( $response, true );
            if (isset( $responseArray[0]["code"] ) ) {
                return !$responseArray[0]["code"];
                $this->toDebugLog( "Logout successfull" );
            } else {
                $this->toDebugLog( "Logout failed" );
                return false;
            }
        }

        protected function ReolinkGetMdState( $Token, $channel = 0 ) {
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=GetMdState&token=".$Token );
            $command["cmd"] = "GetMdState";
            $command["param"]["channel"] = $channel;
            $jsonParam = "[".json_encode( $command )."]";
            curl_setopt($ch, CURLOPT_POST, 1) ;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParam );
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec($ch); 
            $responseArray = json_decode( $response, true );
            curl_close( $ch );
            if (isset( $responseArray[0]["code"] ) ) {
                if ( $responseArray[0]["code"] == 0 ) {
                    $this->toDebugLog( "Motion detection status retrieved" );
                    return $responseArray[0]["value"]["state"];
                } else
                    $this->toDebugLog( "Motion detection status could not be retrieved" );
                    return false;
            } else {
                $this->toDebugLog( "No data received on Motion detection status retrieval" );
                return false;
            }
        }
        
        protected function toDebugLog( $string ) {
          if ( $this->ReadPropertyBoolean("DebugLog") == true ) {
              $this->SendDebug( "Reolink Camera", $string );
          }
        }
        
    }
?>