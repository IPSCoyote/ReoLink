        protected function ping($host, $port, $timeout) 
        { 
            ob_start();
            $fP = fSockOpen($host, $port, $errno, $errstr, $timeout); 
            ob_clean();
            if (!$fP) { return false; } 
            return true; 
        }
        