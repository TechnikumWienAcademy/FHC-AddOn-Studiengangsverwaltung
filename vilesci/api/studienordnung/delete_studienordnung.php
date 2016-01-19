<?php

require_once('../../../../../config/vilesci.config.inc.php');
require_once('../../../../../include/functions.inc.php');
require_once('../../../../../include/benutzerberechtigung.class.php');
require_once('../../../include/StudienordnungAddonStgv.class.php');
require_once('../../../include/StudienplanAddonStgv.class.php');
require_once('../functions.php');

$uid = get_uid();

$berechtigung = new benutzerberechtigung();
$berechtigung->getBerechtigungen($uid);
if($berechtigung->isBerechtigt("stgv/deleteStudienordnung", null, "suid"))
{
    $studienordnung_id = filter_input(INPUT_GET, "studienordnung_id");

    if(is_null($studienordnung_id))
    {
	returnAJAX(false, "Variable studienordnung_id nicht gesetzt");    
    }
    elseif($studienordnung_id == false)
    {
	returnAJAX(false, "Fehler beim lesen der GET Variablen");    
    }

    $studienordnung = new StudienordnungAddonStgv();
    $studienordnung->loadStudienordnung($studienordnung_id);
	$studienplan = new StudienplanAddonStgv();
	
    if($studienordnung->status_kurzbz == "development")
    {
		$studienplan->loadStudienplanSTO($studienordnung_id);
		if(count($studienplan->result) > 0)
		{
			$error = array("message"=>"Studienordnung kann nicht gelöscht werden. Es sind noch Studienpläne verknüpft.", "detail"=>$studienplan->errormsg);
			returnAJAX(false, $error);
		}

		if($studienordnung->delete($studienordnung_id))
		{
			returnAJAX(true, "Studienordnung erfolgreich gelöscht");
		}
		else
		{
			$error = array("message"=>"Fehler beim Löschen des Studienplans.", "detail"=>$studienordnung->errormsg);
			returnAJAX(false, $error);
		}
    }
    else
    {
	$error = array("message"=>"Studienordnung kann in diesem Status nicht gelöscht werden." , "detail"=>"");
	returnAJAX(false, $error);
    }
}
 else
{
    $error = array("message"=>"Sie haben keine Berechtigung.", "detail"=>"stgv/deleteStudienordnung");
    returnAJAX(false, $error);
}

?>