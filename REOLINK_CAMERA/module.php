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
          $this->RegisterPropertyString("IPAddressDevice", "0.0.0.0"); 
          $this->RegisterPropertyInteger("UpdateFrequency", 0);  
            
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
          $this->Update();
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
            SetValue($this->GetIDForIdent("status"),                  $goEChargerStatus->{'car'});    
            SetValue($this->GetIDForIdent("availableAMP"),            $goEChargerStatus->{'amp'}); 
            SetValue($this->GetIDForIdent("error"),                   $goEChargerStatus->{'err'}); 
            SetValue($this->GetIDForIdent("accessControl"),           $goEChargerStatus->{'ast'});
            SetValue($this->GetIDForIdent("accessState"),             $goEChargerStatus->{'alw'}); 
            SetValue($this->GetIDForIdent("cableCapability"),         $goEChargerStatus->{'cbl'});
            SetValue($this->GetIDForIdent("rebootCounter"),           $goEChargerStatus->{'rbc'});
            SetValue($this->GetIDForIdent("rebootTime"), date( DATE_RFC822, time()-round($goEChargerStatus->{'rbt'}/1000,0)) );
                
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
                          
            $this->SetStatus(102);
            return $goEChargerStatus;
        }
        
        protected function getStatusFromCamera() {
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
                          
            $this->SetStatus(102);
            return $goEChargerStatus;
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
            $this->EnableAction("accessState");   
            
        }
    }
?>