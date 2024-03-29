<?php

    /*=== INCLUDE TRAITS =============================*/
    /*=== COMMON FUNCTIONS ========================== */
    include __DIR__ . '/../libs/commonFunctions.php';
    
    /*=== COMMON FUNCTIONS ========================== */
    include __DIR__ . '/../libs/reolinkFunctions.php';

    class ReolinkCamera extends IPSModule {
 
        use commonFunctions, reolinkFunctions;       

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
          $this->RegisterAttributeString( "Token", ""); 
          $this->RegisterAttributeInteger( "TokenTimeout", 0 ); 
          $this->RegisterAttributeString( "UserAbility", "" );
          $this->RegisterAttributeString( "DeviceInfo", "" );
          
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

		    // check configuration
		    $this->checkConfiguration();

            // Generate Profiles & Variables
            $this->registerProfiles();
            $this->registerVariables();  
		  
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
                SetValue($this->GetIDForIdent("motionDetected"), $this->ReolinkGetMdState() );
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
        
        public function StoreImageProfile( string $profileName ) {
        	$this->toDebugLog( "StoreImageProfile called" );
        	
            /* Login to Camera - here a token is reused, of not logged out before! */
            if ( $this->ReolinkLogin( trim($this->ReadPropertyString("Username")), trim($this->ReadPropertyString("Password")) ) === true ) {
                // Get MD State
                $imageData = array();
                $imageData = $this->ReolinkGetImage( );
                if ( $imageData != false ) {
                    // store data to profile
                    // get existing profiles 
                    $profiles = json_decode( GetValue( $this->GetIDForIdent("imageProfiles") ), true );
                    if ( $profiles == "" ) {
                        $profiles = array();
                    }
                    $profiles[$profileName] = $imageData;
                    return SetValue( $this->GetIDForIdent("imageProfiles"), json_encode( $profiles ) );
                } else {
                    // store of profile failed
                    return false;
                }
            } else {
                $this->toDebugLog( "StoreImageProfile called" );
                return false;
            }
            
            // Login Connection is kept alive
        }
        
        public function RemoveImageProfile( string $profileName ) {
            $this->toDebugLog( "RemoveImageProfile called" );
            $profiles = json_decode( GetValue( $this->GetIDForIdent("imageProfiles") ), true );
            if ( isset( $profiles[$profileName] ) ) {
                // remove image profile
                unset( $profiles[$profileName] );
                return SetValue( $this->GetIDForIdent("imageProfiles"), json_encode( $profiles ) );
            } else {
                return false;
            }
        }
               
        public function RemoveAllImageProfiles( ) {
            $this->toDebugLog( "RemoveAllImageProfiles called" );

            $profiles = array();
            return SetValue( $this->GetIDForIdent("imageProfiles"), json_encode( $profiles ) );
        }
        
        public function ActivateImageProfile( string $profileName ) {
            $this->toDebugLog( "ActivateImageProfile called" );
            $profiles = json_decode( GetValue( $this->GetIDForIdent("imageProfiles") ), true );
            if ( isset( $profiles[$profileName] ) ) { 
                $imageData = $profiles[$profileName];
                if ( $this->ReolinkLogin( trim($this->ReadPropertyString("Username")), trim($this->ReadPropertyString("Password")) ) === true ) {
                    // get abilities
                    $abilities = $this->ReolinkGetAbility();
                    if ( isset( $abilities["abilityChn"][0]["image"]["permit"] ) and $abilities["abilityChn"][0]["image"]["permit"] == 6 ) {
                        // set image profile to camera
                        return $this->ReolinkSetImage( $imageData );
                    } else {
                        $this->toDebugLog( "No sufficient user rights to set image data" );
                        return false;
                    } 
                } else {
                    $this->toDebugLog( "Login failed" );
                    return false;
                }
            } else {
                $this->toDebugLog( "ImageProfile ".$profileName." does not exist" );
                return false;
            }
        }

        public function GetSnapshot( ) {
            $this->toDebugLog( "GetImage called" );
            $image = $this->ReolinkSnap(0 );
            if ($image != false) {
                $mediaID = IPS_GetMediaIDByName("Snapshot", $this->InstanceID);
                if ( $mediaID != false ) {
                    $base64 = base64_encode($image);
                    IPS_SetMediaContent($mediaID, $base64 );
                }
            } else {
                $this->toDebugLog("No Result");
            }
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
            if ( $this->ReolinkLogin() === false ) {
                $this->SetStatus(206); // No Login possible
                return false;
            } else {
                // Login successfull, get user abilities
                $this->ReolinkGetAbility();
                $this->ReolinkGetDevInfo();
                
                // logout
                if ( $this->ReolinkLogout() == false ) {
                       return false;
                }
            }
                   
            $DeviceInfo = json_decode( $this->ReadAttributeString( "DeviceInfo" ), true );
            if ( ( !isset( $DeviceInfo["channelNum"] ) ) OR 
                 ( $DeviceInfo["channelNum"] != 1 ) ) {
              // device is no Camera (more than 1 camera channel)
              $this->SetStatus(207); // No Login possible
              return false;
            }
                   
            $this->SetStatus(102);
            $this->toDebugLog( "Configuration ok" );
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
            
            //--- Settings
            $this->RegisterVariableString("imageProfiles", "ImageProfiles","",50);

            //--- Images
            if (@IPS_GetMediaIDByName("Snapshot", $this->InstanceID) == false ) {
                $createdMediaID = IPS_CreateMedia(MEDIATYPE_IMAGE);
                IPS_SetName($createdMediaID, "Snapshot");
                IPS_Sleep(2000);
                IPS_SetMediaCached($createdMediaID, true);
                IPS_SetMediaFile($createdMediaID, "Snapshot".$this->InstanceID.".jpg", false);
                IPS_SetParent($createdMediaID, $this->InstanceID);
            }
        }
        
        protected function toDebugLog( $string ) {
          if ( $this->ReadPropertyBoolean("DebugLog") == true ) {
              $this->SendDebug( "Reolink Camera", $string, 0 );
          }
        }
        
    }
?>