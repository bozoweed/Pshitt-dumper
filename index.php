<?php
class pshittDumper{    
    private $fileName ="./password_trap.json";// file path;
    private $combotFormat= "{u}:{p}";// output combot format {u} is the username and {p} is the password
    private $blackListUserForMasterCombot = ["z","v"];//user blacklisted
    private $minUserLength = 3;
    private $minPasswordLength = 1;

    /**
     * 
     * 
     * 
     * PLEASE DO NOT TOUCH UNDER THIS UNLESS YOU KNOW WHAT YOU ARE DOING !!!!!!
     * 
     * 
     * 
     * 
     * 
     */

    private $users= array();
    private $passwords= array();
    private $combots= array();
    private $ips= array();
    private $fp = false;

    public function __construct(){ 
        $this->openFile();    
        if($this->fp){
            while (($line = stream_get_line( $this->fp, 1024 * 1024, "\n")) !== false)  $this->readLine($line);            
            $this->closeFile();
        }else die(  $this->fileName." was not found");
             
    }

    private function generateCombotFormated($username, $password){
        return str_replace("{p}",$password, str_replace("{u}",$username, $this->combotFormat));
    }

    private function insertData($data, & $_array){
        if(!in_array($data, $_array)) $_array[]= $data;
    }

    private function openFile(){
        if(is_file( $this->fileName)) $this->fp = fopen( $this->fileName, "r+");
    }

    private function closeFile(){   
        if($this->fp){     
            fclose( $this->fp);
            $this->fp= false;
        }
    }

    private function readLine($line){        
        $Data = json_decode($line);
        $this->insertData($Data->username, $this->users);//insert username
        $this->insertData($Data->src_ip, $this->ips);//insert ips
        $this->insertData($Data->password,  $this->passwords);//insert password 
        $this->insertData( $this->generateCombotFormated($Data->username, $Data->password),  $this->combots); // insert combot
    }

    public function log(){
        die('combots '. count($this->combots).' | users '. count($this->users).' | passwords '. count($this->passwords) ." | MasterCombots ".((count($this->users)) * (count($this->passwords) )).' | ips '. count($this->ips) );
    }

    public function export($type){
        switch($type){
            case"all":
                $this->exportAll();
                break;
            case "combots":
                $this->exportCombots();
                break;
            case "users":
                $this->exportUsers();
                break;
            case "passwords":
                $this->exportPasswords();
                break;
            case "masterCombots":
                $this->exportMasterCombots();
                break;
            case "ips":
                $this->exportIps();
                break;
            default:
                die("export type (".$type.") not exist");
        }
    }

    private function exportAll(){
        $this->exportCombots();
        $this->exportUsers();
        $this->exportMasterCombots();
        $this->exportPasswords();


        $this->exportIps();// keep for the end it's really long ...
    }

    private function checkSshHealth($host){
        $error_code = 0;// read the doc ... it'es just ref so give up x)
        $error_msg = "";// read the doc ... it'es just ref so give up x)
        $conn = @fsockopen($host, 22,$error_code, $error_msg, 2);
        if(is_resource($conn)){
            fclose($conn);
            return true;
        }
        return false;
    }

    private function exportCombots(){
        $this->exportArray("./combots.txt",$this->combots);
    }
    
    private function exportUsers(){
        $this->exportArray("./users.txt",$this->users);
    }

    private function exportPasswords(){
        $this->exportArray("./passwords.txt",$this->passwords);
    }

    private function exportIps(){
        $_array = array();
        foreach($this->ips as $key => $ip) if ($this->checkSshHealth($ip)) $_array[] = $ip;
        $this->exportArray("./ips.txt",$_array);
    }
    
    private function exportMasterCombots(){
        $fileName= "./masterCombots.txt";
        if(is_file($fileName)) unlink($fileName);
        $fp = fopen($fileName,"w+");
        $count = (count($this->users)) * (count($this->passwords) );
        foreach($this->users as $keyu => $username){
            if(in_array($username, $this->blackListUserForMasterCombot) or strlen($username) < $this->minUserLength) continue;
            foreach ($this->passwords as $keyp => $password) {
                if(strlen($password) < $this->minPasswordLength)  continue;
                fwrite($fp, $this->generateCombotFormated($username, $password). (($keyu+1) * ($keyp+1)!=$count ?  PHP_EOL : ""));
            }
        }      
        fclose($fp);
    }

    private function exportArray($fileName, $_array){
        if(is_file($fileName)) unlink($fileName);
        $fp = fopen($fileName,"w+");
        $count = count($_array) - 1;
        foreach($_array as $key=>$data) fwrite($fp, $data. ($key!=$count ?  PHP_EOL : ""));        
        fclose($fp);
    }

}

$instance = new pshittDumper();

if(isset($_GET["export"])){
    $instance->export($_GET["export"]);
}


$instance->log();