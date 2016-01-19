<?php

require_once('../../../../../../config/vilesci.config.inc.php');
require_once('../../../../../../include/functions.inc.php');
require_once('../../../../../../include/benutzerberechtigung.class.php');
require_once('../../../../../../include/akadgrad.class.php');
require_once('../../../../../../include/studiensemester.class.php');
require_once('../../../../include/StudienordnungAddonStgv.class.php');
require_once('../../functions.php');

$sto_array = array();

$stoId = filter_input(INPUT_GET, "stoId");

if(is_null($stoId))
{
    returnAJAX(false, "Variable stoId nicht gesetzt");    
}
elseif(($stoId == false))
{
    returnAJAX(false, "Fehler beim lesen der GET Variablen");    
}


$studienordnung = new StudienordnungAddonStgv();
$studienordnung->loadStudienordnung($stoId);

$akadgrad = new akadgrad();
$akadgrad->load($studienordnung->akadgrad_id);
$akadgrad->getAll();
$studiensemester = new studiensemester();
$studiensemester->getAll();

$data = array(
    'studienordnung_id'=> $studienordnung->studienordnung_id,
    'stgkz'=> $studienordnung->studiengang_kz,
    'version'=> $studienordnung->version, 				
    'bezeichnung' => $studienordnung->bezeichnung,				
    'ects' => $studienordnung->ects,
    'gueltigvon' => $studienordnung->gueltigvon,
    'gueltigbis' => $studienordnung->gueltigbis,
    'studiengangbezeichnung' => $studienordnung->studiengangbezeichnung,
    'studiengangbezeichnung_englisch' => $studienordnung->studiengangbezeichnung_englisch,
    'studiengangkurzbzlang' => $studienordnung->studiengangkurzbzlang,
    'akadgrad_id' => $studienordnung->akadgrad_id,
    'studiengangsart' => $studienordnung->studiengangsart,
    'standort_id' => $studienordnung->standort_id,
    'orgform_kurzbz' => $studienordnung->orgform_kurzbz,
    'status_kurzbz' => $studienordnung->status_kurzbz,
    'updateamum' => $studienordnung->updateamum,
    'updatevon' => $studienordnung->updatevon,
    'insertamum' => $studienordnung->insertamum,
    'insertvon' => $studienordnung->insertvon
);


returnAJAX(true, $data);
?>