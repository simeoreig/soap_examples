<?php

define("USERNAME", "YOUR_LOGIN");  // CHANGE IT FOR YOUR REAL LOGIN/PASSWORD
define("PASS", "YOUR_PASSWORD");   // CHANGE IT FOR YOUR REAL LOGIN/PASSWORD
define("WSDL","INCOFISA_WSDL");    //CHANGE IT FOR INCOFISA'S WSDL (see docs)


    $myWebService = new WebServiceIncofisa(USERNAME,PASS, WSDL,true);

    $aPetitions = array(		    
		    array(
		    'productID' => 1,
		    'productBehaviour' => 0,
		    'yourReference' => '',
		    'nationalID' => '99999999R', // Wrong nationalID
		    'name' => 'juan',
		    'firstSurname' => 'sanchez',
		    'secondSurname' => '',
		    'birthDate' => '',
		    'address' => '',
		    'postalCode' => '',
		    'city' => '',
		    'provinceCode' => '',
		    'phones' => ''
		    ),
		    array(
		    'productID' => 1,
		    'productBehaviour' => 0,
		    'yourReference' => '',
		    'nationalID' => '39999999D',
		    'name' => 'juan',
		    'firstSurname' => '',         // Left blank, error
		    'secondSurname' => '',
		    'birthDate' => '',
		    'address' => '',
		    'postalCode' => '',
		    'city' => '',
		    'provinceCode' => '',
		    'phones' => ''
		    ),
		    array(
		    'productID' => 1,
		    'productBehaviour' => 0,
		    'yourReference' => '',
		    'nationalID' => '39999999D',
		    'name' => 'juan',
		    'firstSurname' => 'sanchez',
		    'secondSurname' => '',
		    'birthDate' => '',
		    'address' => '',
		    'postalCode' => '',
		    'city' => '',
		    'provinceCode' => '',
		    'phones' => ''
		    ),		     
		);   
		

    
	foreach ($aPetitions as $myPetition) {
	    		
	    try {	
		$resultPetition = $myWebService->doInstantPetition($myPetition);
		
		echo "\n Petition result is $resultPetition";
	
	    } catch (Exception $fault) {    
		echo "\n There is an error on petition : ".$fault->getMessage();
	    }
	}


    exit(0);






    /**
    * Incofisa SOAP webservice example PHP
    *
    *
    * @copyright  2021 Grupo Incofisa
    * @license    Restricted
    * @version    Release: 0.1
    * @link       https://www.grupoincofisa.com
    * @since      Class available since 8-2021
    * @author     Simeo Reig <simeo.reig@adronica.com>
    */ 
    class WebServiceIncofisa {
    
        private string $login;
        private string $password;
        private soapclient $soapClient;
        private bool $logConsole;
        private string $wsdl;

        const ERROR_EXPIRED_INEXISTENT_SESSION = 999; 
        const ERROR_TIMEOUT = 987;

        function __construct(string $login, string $password, string $wsdl, bool $logConsole) {

            $this->login = $login;
            $this->password = $password;
            $this->logConsole = $logConsole;
            $this->wsdl = $wsdl;

            $this->soapClient = new SoapClient($this->wsdl);

        }


        /**
         * call remote soap method 'doInstantPetition'
         *
         * @param arrayPetition array of petition 
        * 
        * @throws Exception 
        * @return XML with result
        */     
        public function doInstantPetition(array $arrayPetition) {

            $payLoad = "";
            $petitionID = -1;
            $faultNumber = 0;
            $faultDescription = "";

            do {

                $recoverableError = false;
                $numAttemps++;

                try {

                    $this->logToConsole("Calling method doInstantPetition");
                    $response = $this->soapClient->doInstantPetition($arrayPetition);
                    $petitionID = $response->return->petitionid;

                    if ( $response->return->statepetitionid == 4) {
                        $payLoad = $response->return->xmlproductresult;
                    } else {
                        $this->logToConsole("\t[ERROR] Something went wrong, don't worry not billed");
                    }

                 } catch (SoapFault $fault) {

                  $faultNumber = $fault->detail->IncofisaException->numeroCodigoError;
                  $faultDescription = $fault->faultstring;

                  $this->logToConsole($faultDescription);

                  switch ($faultNumber) {

                    case self::ERROR_EXPIRED_INEXISTENT_SESSION:
                      $recoverableError = $this->doLogin();
                      break;
                    case self::ERROR_TIMEOUT:		
                      $recoverableError = true;
                      break;
                   }
                }

            } while ($recoverableError && $numAttemps < 3);


            if ($payLoad != "") {
              $this->logToConsole("\tPetition id $petitionID resolved successfuly, its payload is $payLoad");
              return $payLoad;
            } else {
              $this->logToConsole("\tPetition not resolved, faultNumber:$faultNumber, faultDescription:$faultDescription");	    
              throw new Exception("$faultNumber::$faultDescription");
            }		

        }
    
        /**
        * call remote soap method 'userLogin'
        *
        * @param none 
        * 
        * @throws Exception NO
        * @return true if login is successfuly
        */     
        private function doLogin() {

            $loginDone = false;

                $this->logToConsole("Calling method doLogin");

                $response = $this->soapClient->userLogin(array('user' => $this->login,'pass' => $this->password));

                if ($response->return == 1) {
                  $this->logToConsole("\tLogin Done");
                  $loginDone = true;
                } else {
                  $this->logToConsole("\t[ERROR] Login does not work");
                }

                return $loginDone;

          }

        /**
        * Do simple login to console
        *
        * @param none 
        * 
        * @throws Exception NO
        * @return void
        */     
        private function logToConsole(string $text){	
            if ($this->logConsole)  echo "\n\t[WebServiceIncofisa] $text";
          }

}

?>


