<?php
/*********************************************************************
    install.php

    osTicket Installer.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('setup.inc.php');

//define('OSTICKET_CONFIGFILE','../include/ost-config.php'); //osTicket config file full path.
define('OSTICKET_CONFIGFILE','../include/ost-config.php'); //XXX: Make sure the path is corrent b4 releasing.


$installer = new Installer(OSTICKET_CONFIGFILE); //Installer instance.
$wizard=array();
$wizard['title']='osTicket Installer';
$wizard['tagline']='Installing osTicket v'.$installer->getVersionVerbose();
$wizard['logo']='logo.png';
$wizard['menu']=array('Installation Guide'=>'http://osticket.com/wiki/Installation',
        'Get Professional Help'=>'http://osticket.com/support');

if($_POST && $_POST['s']) {
    $errors = array();
    $_SESSION['installer']['s']=$_POST['s'];
    switch(strtolower($_POST['s'])) {
        case 'prereq':
            if($installer->check_prereq())
                $_SESSION['installer']['s']='config';
            else
                $errors['prereq']='Minimum requirements not met!';
            break;
        case 'config':
            if(!$installer->config_exists())
                $errors['err']='Configuratin file does NOT exist. Follow steps below'.$installer->getConfigFile();
            elseif(!$installer->config_writable())
                $errors['err']='Write access required to continue';
            else
                $_SESSION['installer']['s']='install';
            break;
        case 'install':
            if($installer->install($_POST)) {
                $_SESSION['info']=array('name'  =>ucfirst($_POST['fname'].' '.$_POST['lname']),
                                        'email' =>$_POST['admin_email'],
                                        'URL'=>URL);
                //TODO: Go to subscribe step.
                $_SESSION['installer']['s']='done';
            } elseif(!($errors=$installer->getErrors()) || !$errors['err']) {
                $errors['err']='Error installing osTicket - correct the errors below and try again.';
            }
            break;
        case 'subscribe':
            if(!trim($_POST['name']))
                $errors['name'] = 'Required';

            if(!$_POST['email'])
                $errors['email'] = 'Required';
            elseif(!Validator::is_email($_POST['email']))
                $errors['email'] = 'Invalid';

            if(!$_POST['alerts'] && !$_POST['news'])
                $errors['notify'] = 'Check one or more';

            if(!$errors)
                $_SESSION['installer']['s'] = 'done';
            break;
    }

}elseif($_GET['s'] && $_GET['s']=='ns' && $_SESSION['installer']['s']=='subscribe') {
    $_SESSION['installer']['s']='done';
}


switch(strtolower($_SESSION['installer']['s'])) {
    case 'config':
    case 'install':
        if(!$installer->config_exists()) {
            $inc='file-missing.inc.php';
        } elseif(!($cFile=file_get_contents($installer->getConfigFile())) 
                || preg_match("/define\('OSTINSTALLED',TRUE\)\;/i",$cFile)) { //osTicket already installed or empty config file?
            $inc='file-unclean.inc.php';
        } elseif(!$installer->config_writable()) { //writable config file??
            clearstatcache();
            $inc='file-perm.inc.php';
        } else { //Everything checked out show install form.
            $inc='install.inc.php'; 
        }
        break;
    case 'subscribe': //TODO: Prep for v1.7 RC1 
       $inc='subscribe.inc.php';
        break;
    case 'done':
        $inc='install-done.inc.php';
        if(!$installer->config_exists())
            $inc='install-prereq.inc.php';
        break;
    default:
         $inc='install-prereq.inc.php';
}

require(INC_DIR.'header.inc.php');
require(INC_DIR.$inc);
require(INC_DIR.'footer.inc.php');
?>