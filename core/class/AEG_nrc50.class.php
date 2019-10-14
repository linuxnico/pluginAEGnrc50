<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class AEG_nrc50 extends eqLogic {
    /*     * *************************Attributs****************************** */

    //tableau des oids snmp des alimentaions aeg nrc50
    const oid = array('oidAegCourantCharge'=> '1.3.6.1.4.1.15416.999.4.2.0',
                      'oidAegTensionCharge'=> '1.3.6.1.4.1.15416.999.4.1.0',
                      'oidAegCourantBatterie'=> '1.3.6.1.4.1.15416.999.3.1.2.2',
                      'oidAegTensionBatterie'=> '1.3.6.1.4.1.15416.999.3.1.2.1',
                      'oidAegTensionSource1'=> '1.3.6.1.4.1.15416.999.1.1.0',
                      'oidAegTensionSource2'=> '1.3.6.1.4.1.15416.999.1.2.0',
                      'oidAegTensionSource3'=> '1.3.6.1.4.1.15416.999.1.3.0',
                      'oidAegTemperature'=> '1.3.6.1.4.1.15416.999.3.1.4.1'
         );


    /*     * ***********************Methode static*************************** */
    //gestion des dependances
    public static function dependancy_info() {
       $return = array();
       $return['progress_file'] = '/tmp/AEG_nrc50_dep';
       $return['log'] = 'AEG_nrc50_dep';
       $test = exec("sudo dpkg-query -l 'php*-snmp*' | grep php", $ping, $retour);
       if(count($ping)>0)
       {
         $return['state'] = 'ok';
       } else {
         $return['state'] = 'nok';
       }
       return $return;
     }
    //install des dependances
    public function dependancy_install() {
      log::add('AEG_nrc50','info','Installation des dependances php-snmp');
      passthru('sudo apt install php-snmp -y >> ' . log::getPathToLog('AEG_nrc50_dep') . ' 2>&1 &');
    }
    // creation de staches cron suivant config de l'equipement
    public static function cron() {
  		$dateRun = new DateTime();
      // log::add('AEG_nrc50', 'debug', "on passe par le cron");
  		foreach (eqLogic::byType('AEG_nrc50') as $eqLogic) {
  			$autorefresh = $eqLogic->getConfiguration('autorefresh');
  			if ($eqLogic->getIsEnable() == 1 && $autorefresh != '') {
  				try {
  					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
  					if ($c->isDue($dateRun)) {
              $cmd = $eqLogic->getCmd(null, 'refresh');//retourne la commande "refresh si elle existe
    				  if (!is_object($cmd)) {//Si la commande n'existe pas
                // log::add('AEG_nrc50', 'debug', "pas de commande refresh ". $eqLogic->getHumanName());
    				  	continue; //continue la boucle
    				  }
              // log::add('AEG_nrc50', 'debug', "on passe par le cron ET on refresh ". $eqLogic->getHumanName());
    				  $cmd->execCmd(); // la commande existe on la lance
  					}
  				} catch (Exception $exc) {
  					log::add('AEG_nrc50', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
  				}
  			}
  		}
  	}


    /*     * *********************Méthodes d'instance************************* */
    //fonction de recuperation de l'etat d'une des 76 alarmes
    // public function alarme($i) {
    //
    //   // log::add('AEG_nrc50','debug', "on recup l'alarme ".$i);
    //   $ip = $this->getConfiguration("ip");
    //   $val = snmpget($ip, "public", self::oid['oidAegAlarme'].$i);
    //   $val = substr($val, strpos($val, ':')+1);
    //   // log::add('AEG_nrc50','debug', "resultat: ".$val);
    //   if ($val==0) //on inverse
    //   {return 1;}
    //   return 0;
    // }

    //fonction de recuperation de la tension source
    public function tensionSource($n) {
      $ip = $this->getConfiguration("ip");


      if ($n=='1') {
        $val1 = snmpget($ip, "public", self::oid['oidAegTensionSource1']);
        $val = str_replace('"', '', substr($val1, strpos($val1, ':')+2));
      } else if ($n=='2'){
        $val2 = snmpget($ip, "public", self::oid['oidAegTensionSource2']);
        $val = str_replace('"', '', substr($val2, strpos($val2, ':')+2));
      } else {

              $val3 = snmpget($ip, "public", self::oid['oidAegTensionSource3']);
              $val = str_replace('"', '', substr($val3, strpos($val3, ':')+2));
      }

      log::add('AEG_nrc50','debug', "on recup : -".floatval($val)."-");
      return floatval($val);
    }
    //fonction de recuperation de la temperature
    public function temperature() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegTemperature']);
      log::add('AEG_nrc50','debug', "on recup temp: ".$val);
      $val = str_replace('"', '', substr($val, strpos($val, ':')+2));
      log::add('AEG_nrc50','debug', "on recup temp: ".$val);
      return $val;
    }
    //fonction de recuperation de la tension de la charge
    public function tensionCharge() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegTensionCharge']);
      $val = str_replace('"', '', substr($val, strpos($val, ':')+2));
      log::add('AEG_nrc50','debug', "on recup tensioncharge: ".$val);
      return $val;
    }
    //fonction de recuperation de la tension Batterie
    public function tensionBatterie() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegTensionBatterie']);
      $val = str_replace('"', '', substr($val, strpos($val, ':')+2));
      log::add('AEG_nrc50','debug', "on recup la tension batterie: ".$val);
      return $val;
    }
    //fonction de recuperation du courant de la charge
    public function courantCharge() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegCourantCharge']);
      $val = str_replace('"', '', substr($val, strpos($val, ':')+2));
      log::add('AEG_nrc50','debug', "on recup : ".$val);
      return $val;
    }
    //fonction de recuperation du courant batterie
    public function courantBatterie() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegCourantBatterie']);
      $val = str_replace('"', '', substr($val, strpos($val, ':')+2));
      log::add('AEG_nrc50','debug', "on recup : ".$val);
      return $val;
    }
    //fonction de vrification de la presence de l'equipement sur le reseau
    public function ping() {
      $ip = $this->getConfiguration("ip");
      $ping = exec("ping -c 1 ".$ip, $ping, $return);
      if($return=='1')
      {
         return 0;
      }
      else
      {
         return 1;
      }
    }

    public function preInsert() {

    }

    public function postInsert() {

    }
    // renseigne l'autorefresh si vide
    public function preSave() {
      if ($this->getConfiguration('autorefresh') == '') {
			     $this->setConfiguration('autorefresh', '*/30 * * * *');
		  }
    }

    public function postSave() {
  // creation commande refresh
      $refresh = $this->getCmd(null, 'refresh');
  		if (!is_object($refresh)) {
  			$refresh = new AEG_nrc50Cmd();
  			$refresh->setName(__('Rafraichir', __FILE__));
  		}
  		$refresh->setEqLogic_id($this->getId());
  		$refresh->setLogicalId('refresh');
  		$refresh->setType('action');
  		$refresh->setSubType('other');
      $refresh->setOrder(1);
      $refresh->setIsHistorized(1);
  		$refresh->save();

// creation commande tension Source1
      $tensionSource = $this->getCmd(null, 'tensionSource1');
  		if (!is_object($tensionSource)) {
  			$tensionSource = new AEG_nrc50Cmd();
  			$tensionSource->setName(__('Tension Source 1', __FILE__));
  		}
  		$tensionSource->setLogicalId('tensionSource1');
  		$tensionSource->setEqLogic_id($this->getId());
  		$tensionSource->setType('info');
  		$tensionSource->setSubType('numeric');
      $tensionSource->setUnite('V');
      $tensionSource->setOrder(3);
      $tensionSource->setIsHistorized(1);
      $tensionSource->setConfiguration("minValue", 0);
      $tensionSource->setConfiguration("maxValue", 250);
  		$tensionSource->save();
// creation commande tension Source2
      $tensionSource = $this->getCmd(null, 'tensionSource2');
  		if (!is_object($tensionSource)) {
  			$tensionSource = new AEG_nrc50Cmd();
  			$tensionSource->setName(__('Tension Source 2', __FILE__));
  		}
  		$tensionSource->setLogicalId('tensionSource2');
  		$tensionSource->setEqLogic_id($this->getId());
  		$tensionSource->setType('info');
  		$tensionSource->setSubType('numeric');
      $tensionSource->setUnite('V');
      $tensionSource->setOrder(3);
      $tensionSource->setIsHistorized(1);
      $tensionSource->setConfiguration("minValue", 0);
      $tensionSource->setConfiguration("maxValue", 250);
  		$tensionSource->save();
// creation commande tension Source3
      $tensionSource = $this->getCmd(null, 'tensionSource3');
  		if (!is_object($tensionSource)) {
  			$tensionSource = new AEG_nrc50Cmd();
  			$tensionSource->setName(__('Tension Source 3', __FILE__));
  		}
  		$tensionSource->setLogicalId('tensionSource3');
  		$tensionSource->setEqLogic_id($this->getId());
  		$tensionSource->setType('info');
  		$tensionSource->setSubType('numeric');
      $tensionSource->setUnite('V');
      $tensionSource->setOrder(3);
      $tensionSource->setIsHistorized(1);
      $tensionSource->setConfiguration("minValue", 0);
      $tensionSource->setConfiguration("maxValue", 250);
  		$tensionSource->save();


// creation commande tension equipement
      $tensionCharge = $this->getCmd(null, 'tensionCharge');
  		if (!is_object($tensionCharge)) {
  			$tensionCharge = new AEG_nrc50Cmd();
  			$tensionCharge->setName(__('Tension Charge 48V', __FILE__));
  		}
  		$tensionCharge->setLogicalId('tensionCharge');
  		$tensionCharge->setEqLogic_id($this->getId());
  		$tensionCharge->setType('info');
  		$tensionCharge->setSubType('numeric');
      $tensionCharge->setUnite('V');
      $tensionCharge->setOrder(5);
      $tensionCharge->setIsHistorized(1);
      $tensionCharge->setConfiguration("minValue", 0);
      $tensionCharge->setConfiguration("maxValue", 108);
  		$tensionCharge->save();

// creation commande courant equipement
      $courantCharge = $this->getCmd(null, 'courantCharge');
  		if (!is_object($courantCharge)) {
  			$courantCharge = new AEG_nrc50Cmd();
  			$courantCharge->setName(__('Courant Charge 48V', __FILE__));
  		}
  		$courantCharge->setLogicalId('courantCharge');
  		$courantCharge->setEqLogic_id($this->getId());
  		$courantCharge->setType('info');
  		$courantCharge->setSubType('numeric');
      $courantCharge->setUnite('A');
      $courantCharge->setOrder(6);
      $courantCharge->setIsHistorized(1);
      $courantCharge->setConfiguration("minValue", 0);
      $courantCharge->setConfiguration("maxValue", 60);
  		$courantCharge->save();

// creation commande tension batterie
      $tensionBatterie = $this->getCmd(null, 'tensionBatterie');
  		if (!is_object($tensionBatterie)) {
  			$tensionBatterie = new AEG_nrc50Cmd();
  			$tensionBatterie->setName(__('Tension Batterie 48V', __FILE__));
  		}
  		$tensionBatterie->setLogicalId('tensionBatterie');
  		$tensionBatterie->setEqLogic_id($this->getId());
  		$tensionBatterie->setType('info');
  		$tensionBatterie->setSubType('numeric');
      $tensionBatterie->setUnite('V');
      $tensionBatterie->setOrder(7);
      $tensionBatterie->setIsHistorized(1);
      $tensionBatterie->setConfiguration("minValue", 0);
      $tensionBatterie->setConfiguration("maxValue", 108);
  		$tensionBatterie->save();
// creation commande courant batterie
      $courantBatterie = $this->getCmd(null, 'courantBatterie');
  		if (!is_object($courantBatterie)) {
  			$courantBatterie = new AEG_nrc50Cmd();
  			$courantBatterie->setName(__('Courant Batterie 48V', __FILE__));
  		}
  		$courantBatterie->setLogicalId('courantBatterie');
  		$courantBatterie->setEqLogic_id($this->getId());
  		$courantBatterie->setType('info');
  		$courantBatterie->setSubType('numeric');
      $courantBatterie->setUnite('A');
      $courantBatterie->setOrder(8);
      $courantBatterie->setIsHistorized(1);
      $courantBatterie->setConfiguration("minValue", 0);
      $courantBatterie->setConfiguration("maxValue", 60);
  		$courantBatterie->save();

      // creation commande temperature
      $temperature = $this->getCmd(null, 'temperature');
  		if (!is_object($temperature)) {
  			$temperature = new AEG_nrc50Cmd();
  			$temperature->setName(__('Temperature', __FILE__));
  		}
  		$temperature->setLogicalId('temperature');
  		$temperature->setEqLogic_id($this->getId());
  		$temperature->setType('info');
  		$temperature->setSubType('numeric');
      $temperature->setUnite('°C');
      $temperature->setOrder(9);
      $temperature->setIsHistorized(1);
      $temperature->setConfiguration("minValue", 0);
      $temperature->setConfiguration("maxValue", 50);
  		$temperature->save();


// creation commande presence reseau
      $presence = $this->getCmd(null, 'presence');
  		if (!is_object($presence)) {
  			$presence = new AEG_nrc50Cmd();
  			$presence->setName(__('Presence IP', __FILE__));
  		}
  		$presence->setLogicalId('presence');
  		$presence->setEqLogic_id($this->getId());
  		$presence->setType('info');
  		$presence->setSubType('binary');
      $presence->setOrder(10);
      $presence->setIsHistorized(1);
  		$presence->save();

    }

    public function preUpdate() {
      // on verifie au'il y a bien une ip de definie
      if ($this->getConfiguration('ip') == '') {
      			throw new Exception(__('L\'adresse IP ne peut etre vide', __FILE__));
      		}
    }

    public function postUpdate() {
    // trop long on ne le fait pas
     // on fait un refresh a la creation et a la mise a jour
      //$cmd = $this->getCmd(null, 'refresh'); // On recherche la commande refresh de l’équipement
  	  //if (is_object($cmd) and $this->getIsEnable() == 1 ) { //elle existe et l'equipement est active, on lance la commande
	//		     $cmd->execCmd();
	//	  }
    }

    public function preRemove() {

    }

    public function postRemove() {

    }

    /*     * **********************Getteur Setteur*************************** */
}

class AEG_nrc50Cmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */

    public function execute($_options = array()) {
        $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
		    switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
			       case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe  .
                 log::add('AEG_nrc50', 'debug', "on passe par le refresh de : ".$eqlogic->getHumanName());
				         // $eqlogic->checkAndUpdateCmd('power', $eqlogic->puissance()); // on met à jour la commande

                 // log::add('AEG_nrc50', 'debug', "on recup tension source: "+$ten);
                 $eqlogic->checkAndUpdateCmd('tensionSource1', $eqlogic->tensionSource('1')); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionSource2', $eqlogic->tensionSource('2')); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionSource3', $eqlogic->tensionSource('3')); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionCharge', $eqlogic->tensionCharge()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionBatterie', $eqlogic->tensionBatterie()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('courantCharge', $eqlogic->courantCharge()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('courantBatterie', $eqlogic->courantBatterie()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('temperature', $eqlogic->temperature()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('presence', $eqlogic->ping()); // on met à jour la commande
                 // if ($this->getConfiguration('alarmes')==1){ // si on a valide la recuperation des etats des alarmes
                 //   for ($i=1; $i<=76; $i++) {
                 //      $eqlogic->checkAndUpdateCmd('alarme'.$i, $this->alarme($i)); // on met à jour la commande
                 //    }
                 // }
				         break;
		         }

    }

    /*     * **********************Getteur Setteur*************************** */
}
