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
          
          // Attributes of the Module
          $this->RegisterAttributeString("Token", ""); 
          $this->RegisterAttributeInteger("TokenTimeout", 0 ); 
          
          // Login handling für method UPDATE
          $this->RegisterPropertyString("LoginToken", "" );
          $this->RegisterPropertyString("LoginTimestamp", "" );


            
          // Timer
          $this->RegisterTimer("ReolinkCamera_UpdateTimer", 0, 'ReolinkCamera_Update($_IPS[\'TARGET\']);');
        }
 
        public function ApplyChanges() {
            /* Called on 'apply changes' in the configuration UI and after creation of the instance */
            $this->toDebugLog( "ApplyChanges called" );
            
            parent::ApplyChanges(); 

            // Generate Profiles & Variables
            $this->registerProfiles();
            $this->registerVariables();  

		    // check configuration
		    $this->checkConfiguration();
		  
            // Set Timer
            if ( $this->ReadPropertyInteger("UpdateFrequency") > 0 ) {
                $this->SetTimerInterval("ReolinkCamera_UpdateTimer", $this->ReadPropertyInteger("UpdateFrequency")*1000);
            } else { 
                $this->SetTimerInterval("ReolinkCamera_UpdateTimer", 0 );
            }
               
            // Set Data to Variables (and update timer)
            // $this->Update();
        } 
        
        public function Destroy() {
            $this->SetTimerInterval( "ReolinkCamera_UpdateTimer", 0 );
            // Never delete this line!
            parent::Destroy();
        }
        
        //=== Modul Funktionen =========================================================================================
        /* Own module functions called via the defined prefix ReolinkCamera_* 
        */
        
        public function Update() {
        	$this->toDebugLog( "Update called" );

            /* Login to Camera - here a token is reused, of not logged out before! */
            if ( $this->ReolinkLogin( trim($this->ReadPropertyString("Username")), trim($this->ReadPropertyString("Password")) ) === true ) {
                // Get MD State
                SetValue($this->GetIDForIdent("motionDetected"), ReolinkGetMdState() );
            } else {
                $this->toDebugLog( "Update failed" );
                return false;
            }

            /* Log out, if time is not active */
            if ( $this->GetTimerInterval( "ReolinkCamera_UpdateTimer" ) == 0 ) {
                $this->ReolinkLogout();
            }
                
            return true;
        }
               
        //=== Modul Funktionen =========================================================================================
        /* Internal Functions
        */
        
        protected function checkConfiguration() {
            $this->toDebugLog( "checkConfiguration called" );
            
            // get IP of Device from configuration
            $IPAddress = trim($this->ReadPropertyString("IPAddressDevice"));

            // check if IP is ocnfigured and valid
            if ( $IPAddress == "0.0.0.0" ) {
                $this->SetStatus(200); // no configuration done
                $this->toDebugLog( "IP not configured" );
                return false;
            } elseif (filter_var($IPAddress, FILTER_VALIDATE_IP) == false) { 
                $this->SetStatus(201); // no valid IP configured
                $this->toDebugLog( "IP Invalid" );
                return false;
            }
            
            // check if any HTTP device on IP can be reached
            if ( $this->ping( $IPAddress, 80, 1 ) == false ) {
                $this->SetStatus(202); // no http response
                $this->toDebugLog( "No ping on IP" );
                return false;
            }
            
            // logout first (if possible) as user/password might have changed
            $this->ReolinkLogout( );
            
            // check user name and login
            if ( ( trim($this->ReadPropertyString("Username")) == "" ) or ( trim($this->ReadPropertyString("Password")) == "" ) ) {
                $this->SetStatus(205); // Invalid or No User Data
                $this->toDebugLog( "No User Data specified" );
                return false;   
            }

            // Try test login and gather user data   
            if ( $this->ReolinkLogin( trim($this->ReadPropertyString("Username")), trim($this->ReadPropertyString("Password")) ) === false ) {
                $this->SetStatus(206); // No Login possible
                return false;
            } else {
                // logout
                if ( $this->ReolinkLogout( ) == false ) {
                       return false;
                }
            }
                   
            $this->SetStatus(102);
            $this->toDebugLog( "Configuration ok" );
            return true;
        }
        
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
            
            // if a token is present but close to timeout (last 5 minutes), first logout and then retrieve a new token
            // if a valid token is present, just confirm true
            if ( ( $this->ReadAttributeString("Token" ) != "" ) and
                 ( $this->ReadAttributeString("TokenTimeout" ) - 300 < time()  ) ) {
                $this->ReolinkLogout( $this->ReadAttributeString ("Token" ) );
            } elseif ( $this->ReadAttributeString ("Token" ) != "" ) {
                return true;
            }
            
            $this->toDebugLog( "ReolinkLogin called" );
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
                $this->WriteAttributeString( "Token", $responseArray[0]["value"]["Token"]["name"] );
                $this->WriteAttributeInteger( "TokenTimeout", time() + $responseArray[0]["value"]["Token"]["leaseTime"] );
                return true;
            } else {
                $this->toDebugLog( "Login failed" );
                return false;
            }
        }

        protected function ReolinkLogout( ) {
            $this->toDebugLog( "ReolinkLogout called" );
            
            if ( $this->ReadAttributeString ("Token" ) == "" ) {
                $this->toDebugLog( "Logout failed as no token present" );
                return false;
            }
            
            $file = "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=Logout&token=".$this->ReadAttributeString("Token" );
            $response = file_get_contents( $file );
            $responseArray = json_decode( $response, true );
            $this->WriteAttributeString( "Token", "" );
            $this->WriteAttributeString( "TokenTimeout", 0 );
            if (isset( $responseArray[0]["code"] ) ) {
                $this->toDebugLog( "Logout successfull" );
                return !$responseArray[0]["code"];

            } else {
                $this->toDebugLog( "Logout failed" );
                return false;
            }
        }

        protected function ReolinkGetMdState( $channel = 0 ) {
            $this->toDebugLog( "ReolinkGetMdState called" );
            
            if ( $this->ReadAttributeString("Token" ) == "" ) {
                $this->toDebugLog( "GetMdState not possible; no Token present; Login first" );
                return false;
            }
            
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=GetMdState&token=".$this->ReadAttributeString("Token" ) );
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
        
        protected function ReolinkGetAbility( $username ) {
            $this->toDebugLog( "ReolinkGetAbility called" );
            
            if ( $this->ReadAttributeString("Token" ) == "" ) {
                $this->toDebugLog( "GetAbility not possible; no Token present; Login first" );
                return false;
            }
            
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=GetAbility&token=".$this->ReadAttributeString("Token" ) );
            $command["cmd"] = "GetAbility";
            $command["param"]["User"]["userName"] = $username;
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
                return $responseArray[0]["value"]["Ability"];
                } else
                    return false;
            } else {
            return false;
            }
        }
        
        protected function ReolinkGetDevInfo( $Token ) {
            $this->toDebugLog( "ReolinkGetDevInfo called" );
            
            if ( $this->ReadAttributeString("Token" ) == "" ) {
                $this->toDebugLog( "GetDevInfo not possible; no Token present; Login first" );
                return false;
            }
            
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=GetDevInfo&token=".$this->ReadAttributeString("Token" ) );
            $command["cmd"] = "GetDevInfo";
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
                return $responseArray[0]["value"]["DevInfo"];
                } else
                    return false;
            } else {
            return false;
            }
        }
        
        protected function toDebugLog( $string ) {
          if ( $this->ReadPropertyBoolean("DebugLog") == true ) {
              $this->SendDebug( "Reolink Camera", $string, 0 );
          }
        }
        
    }
?>