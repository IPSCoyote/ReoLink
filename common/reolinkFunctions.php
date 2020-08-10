        /*=== REOLINK NATIVE FUNCTIONS ============== */
        protected function ReolinkLogin() {
            
            // if a token is present but close to timeout (last 5 minutes), first logout and then retrieve a new token
            // if a valid token is present, just confirm true
            if ( ( $this->ReadAttributeString("Token" ) != "" ) and
                 ( $this->ReadAttributeInteger("TokenTimeout" ) - 300 < time()  ) ) {
                $this->ReolinkLogout( $this->ReadAttributeString ("Token" ) );
            } elseif ( $this->ReadAttributeString ("Token" ) != "" ) {
                return true;
            }
            
            $this->toDebugLog( "ReolinkLogin called" );
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=Login" );
            $command["cmd"] = "Login";
            $command["param"]["User"]["userName"] = trim($this->ReadPropertyString("Username");
            $command["param"]["User"]["password"] = trim($this->ReadPropertyString("Password");
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

        protected function ReolinkLogout() {
            $this->toDebugLog( "ReolinkLogout called" );
            
            if ( $this->ReadAttributeString ("Token" ) == "" ) {
                $this->toDebugLog( "Logout failed as no token present" );
                return false;
            }
            
            $file = "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=Logout&token=".$this->ReadAttributeString("Token" );
            $response = file_get_contents( $file );
            $responseArray = json_decode( $response, true );
            $this->WriteAttributeString( "Token", "" );
            $this->WriteAttributeInteger( "TokenTimeout", 0 );
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
        
        protected function ReolinkGetAbility() {
            $this->toDebugLog( "ReolinkGetAbility called" );
            
            $this->WriteAttributeString( "UserAbility", "" );
            
            if ( $this->ReadAttributeString("Token" ) == "" ) {
                $this->toDebugLog( "GetAbility not possible; no Token present; Login first" );
                return false;
            }
            
            $ch = curl_init( "http://".trim($this->ReadPropertyString("IPAddressDevice"))."/api.cgi?cmd=GetAbility&token=".$this->ReadAttributeString("Token" ) );
            $command["cmd"] = "GetAbility";
            $command["param"]["User"]["userName"] = trim($this->ReadPropertyString("Username");
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
                    $this->WriteAttributeString( "UserAbility", json_encode( $responseArray[0]["value"]["Ability"] ) );
                return true;
                } else
                    return false;
            } else {
            return false;
            }
        }
        
        protected function ReolinkGetDevInfo() {
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