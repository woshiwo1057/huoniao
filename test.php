<?php
header("Content-type: text/html; charset=utf-8");
class GetMac{
    var $result   = array(); 
    var $macAddrs = array(); //所有mac地址
    var $macAddr;            //第一个mac地址
 
    function __construct($OS){
        $this->GetMac($OS);
    }
 
    function GetMac($OS){
        switch ( strtolower($OS) ){
        	case "unix": break;
        	case "solaris": break;
        	case "aix": break;
        	case "linux":
        	    $this->getLinux();
        	    break;
        	default: 
        	    $this->getWindows();
        	    break;
        }
        $tem = array();
        foreach($this->result as $val){
            if(preg_match("/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i",$val,$tem) ){
                $this->macAddr = $tem[0];//多个网卡时，会返回第一个网卡的mac地址，一般够用。
                break;
                //$this->macAddrs[] = $temp_array[0];//返回所有的mac地址
            }
        }
        unset($temp_array);
        return $this->macAddr;
    }
    //Linux系统
    function getLinux(){
        @exec("ifconfig -a", $this->result);
        return $this->result;
    }
    //Windows系统
    function getWindows(){
        @exec("arp 192.168.1.1 -a", $this->result);
        if ( $this->result ){
            return $this->result;
        } else {
            $ipconfig = $_SERVER["WINDIR"]."\system32\arp.exe";
            if(is_file($ipconfig)) {
                @exec($ipconfig." -a", $this->result);
            } else {
                @exec($_SERVER["WINDIR"]."\system\arp.exe 192.168.1.1 -a", $this->result);
                return $this->result;
            }
        }
    }
}



$obj = new GetMac(PHP_OS);
//print_r($obj->result);
echo $obj->macAddr;


