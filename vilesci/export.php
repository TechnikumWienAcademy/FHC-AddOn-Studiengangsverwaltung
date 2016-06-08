<?php
/* Copyright (C) 2016 fhcomplete.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/dokument_export.class.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/studienordnung.class.php');
require_once('../../../include/akadgrad.class.php');
require_once('../../../include/organisationsform.class.php');
require_once('../../../include/standort.class.php');
require_once('../../../include/lehrform.class.php');
require_once('../include/studienordnungAddonStgv.class.php');
require_once('../include/studienplanAddonStgv.class.php');
require_once('../include/aenderungsvariante.class.php');
require_once('../include/beschluss.class.php');
require_once('../include/zugangsvoraussetzung.class.php');
require_once('../include/aufnahmeverfahren.class.php');
require_once('../include/taetigkeitsfeld.class.php');
require_once('../include/qualifikationsziel.class.php');
require_once('../include/auslandssemester.class.php');
require_once('../include/berufspraktikum.class.php');

// Addon LVINFO
require_once('../../lvinfo/include/lvinfo.class.php');

$uid = get_uid();

$datum_obj = new datum();

// TODO Berechtigungen
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);
if(!$rechte->isBerechtigt('admin'))
	die($rechte->errormsg);

$output='pdf';

if(isset($_GET['output']) && ($output='odt' || $output='doc'))
	$output=$_GET['output'];

if(isset($_GET['studienordnung_id']) && is_numeric($_GET['studienordnung_id']))
	$studienordnung_id = $_GET['studienordnung_id'];
else
	die('StudienordnungID muss uebergeben werden');

if(isset($_GET['lvinfo']))
{
	if($_GET['lvinfo']=='true')
		$output_lvinfo=true;
	else
		$output_lvinfo=false;
}
else
{
	$output_lvinfo=false;
}

$doc = new dokument_export('STGV_Sto');

$studienordnung = new StudienordnungAddonStgv();
$studienordnung->loadStudienordnung($studienordnung_id);

$studienordnungstatus = new studienordnung();
$studienordnungstatus->getStatus();
foreach($studienordnungstatus->result as $row_status)
	$status_arr[$row_status->status_kurzbz]=$row_status->bezeichnung;

$studiengang = new studiengang();
$studiengang->load($studienordnung->studiengang_kz);

// Aenderungsvariante
$aenderungsvarianten = new Aenderungsvariante();
$aenderungsvarianten->getAll();
$av_arr = array();
foreach($aenderungsvarianten->result as $row)
	$av_arr[$row->aenderungsvariante_kurzbz]=$row->bezeichnung;

// Beschluesse
$beschluss = new beschluss();
$beschluss->getAll($studienordnung_id);
$beschluesse = array('Studiengang'=>'', 'Kollegium'=>'', 'AQ Austria'=>'');

foreach($beschluss->result as $row_beschluss)
	$beschluesse[$row_beschluss->typ]=$datum_obj->formatDatum($row_beschluss->datum,'d.m.Y');

// Akadgrad
$akadgrad = new akadgrad();
$akadgrad->load($studienordnung->akadgrad_id);

// Orgform
$orgform = new organisationsform();
$orgform->load($studiengang->orgform_kurzbz);

// Studiengangstyp
$studiengang->getAllTypes();

// Standort
$standort = new standort();
$standort->load($studienordnung->standort_id);

// ZGV
$zugangsvoraussetzung = new zugangsvoraussetzung();
$zugangsvoraussetzung->getAll($studienordnung_id);

// Aufnahmeverfahren
$aufnahmeverfahren = new aufnahmeverfahren();
$aufnahmeverfahren->getAll($studienordnung_id);

$lehrform = new lehrform();
$lehrform->getAll();
$lehrform_arr=array();
foreach($lehrform->lehrform as $row)
	$lehrform_arr[$row->lehrform_kurzbz] = $row->bezeichnung;

// Studienplan
$stpl = new StudienplanAddonStgv();
$stpl->loadStudienplanSTO($studienordnung_id);
$stpl_arr = array();
foreach($stpl->result as $row_stpl)
{
	$stpl_orgform = new organisationsform();
	$stpl_orgform->load($row_stpl->orgform_kurzbz);

	$summe_ects=0;
	$summe_sws=0;
	$summe_lvs=0;

	$semester_arr=array();
	for($sem=1; $sem<=$row_stpl->regelstudiendauer; $sem++)
	{
		$semester_summe_ects=0;
		$semester_summe_sws=0;
		$semester_summe_lvs=0;

		$lv = new lehrveranstaltung();
		$lv->loadLehrveranstaltungStudienplan($row_stpl->studienplan_id, $sem);
		$tree = $lv->getLehrveranstaltungTree();

		foreach($tree as $lv)
			$semester_summe_ects += $lv->ects;

		$lv_arr = PrintLVTree($tree);

		$semester_arr[]=array('ausbildungssemester'=>
		array(
			'semester'=>$sem,
			'semester_summe_ects'=>$semester_summe_ects,
			'semester_summe_sws'=>$semester_summe_sws,
			'semester_summe_lvs'=>$semester_summe_lvs,
			'lehrveranstaltungen'=>$lv_arr,
		));
		$summe_ects += $semester_summe_ects;
		$summe_sws += $semester_summe_sws;
		$summe_lvs += $semester_summe_lvs;
	}

	// Gueltigkeit
	$gueltig_ab_studiensemester='';
	$gueltig_ab_ausbildungssemester='';
	$stpl_gueltig = new studienplan();
	$stpl_gueltig_stsem_arr = $stpl_gueltig->loadStudiensemesterFromStudienplan($row_stpl->studienplan_id);

	if(isset($stpl_gueltig_stsem_arr[0]))
	{
		$gueltig_ab_studiensemester=$stpl_gueltig_stsem_arr[0];
		$stpl_gueltig_ausbsem_arr = $stpl_gueltig->loadAusbildungsemesterFromStudiensemester($row_stpl->studienplan_id, $stpl_gueltig_stsem_arr[0]);
		$gueltig_ab_ausbildungssemester = implode($stpl_gueltig_ausbsem_arr,' / ');
	}

	// Auslandssemester
	$auslandssemester = new auslandssemester();
	$auslandssemester->getAll($row_stpl->studienplan_id);
	$auslandssemester_semester=array();
	$auslandssemester_erlaeuterungen='';
	if(isset($auslandssemester->result[0]))
	{
		$auslandssemester_erlaeuterungen = $auslandssemester->result[0]->erlaeuterungen;

		foreach($auslandssemester->result[0]->data as $auslsem_sem=>$row_auslsem)
		{
			if($row_auslsem->verpflichtend || $row_auslsem->optional)
			{
				$auslandssemester_semester[] = array ('semester'=>array(
						'semester'=>$auslsem_sem+1,
						'verpflichtend'=>($row_auslsem->verpflichtend?'true':'false')
				));
			}
		}
	}

	// Berufspraktikum
	$berufspraktikum = new berufspraktikum();
	$berufspraktikum->getAll($row_stpl->studienplan_id);
	$berufspraktikum_erlaeuterungen = '';
	$berufspraktikum_semester=array();
	if(isset($berufspraktikum->result[0]))
	{
		$berufspraktikum_erlaeuterungen = $berufspraktikum->result[0]->erlaeuterungen;

		foreach($berufspraktikum->result[0]->data as $bpraksem=>$row_bprak)
		{
			if($row_bprak->semester)
			{
				$berufspraktikum_semester[] = array ('semester'=>array(
						'semester'=>$bpraksem+1,
						'ects'=>$row_bprak->ects,
						'wochen'=>$row_bprak->dauer,
				));
			}
		}
	}

	$stpl_arr[]=array('studienplan'=>array(
		'version'=>$row_stpl->version,
		'organisationsform'=>$stpl_orgform->bezeichnung,
		'regelstudiendauer'=>$row_stpl->regelstudiendauer,
		'pflicht_sws'=>$row_stpl->pflicht_sws,
		'pflicht_lvs'=>$row_stpl->pflicht_lvs,
		'sprache'=>$row_stpl->sprache,
		'sprache_kommentar'=>$row_stpl->sprache_kommentar,
		'semester'=>$semester_arr,
		'summe_ects'=>$summe_ects,
		'summe_sws'=>'', // TODO $summe_sws nicht korrekt bei WahlLVs,
		'summe_lvs'=>'', // TODO $summe_lvs nicht korrekt bei WahlLVs,
		'gueltig_ab_studiensemester'=>$gueltig_ab_studiensemester,
		'gueltig_ab_ausbildungssemester'=>$gueltig_ab_ausbildungssemester,
		'erlaeuterungen'=>$row_stpl->erlaeuterungen,
		'auslandssemester_erlaeuterungen'=>$auslandssemester_erlaeuterungen,
		'auslandssemester'=>$auslandssemester_semester,
		'berufspraktikum_erlaeuterungen'=>$berufspraktikum_erlaeuterungen,
		'berufspraktikum'=>$berufspraktikum_semester,

	));
}

// Taetigkeitsfeld
$taetigkeitsfeld_ueberblick = '';
$taetigkeitsfeld = new taetigkeitsfeld();
$taetigkeitsfeld->getAll($studienordnung_id);
if(isset($taetigkeitsfeld->result[0]))
{
	$taetigkeitsfeld_ueberblick = $taetigkeitsfeld->result[0]->ueberblick;
	$branchen_fixed = $taetigkeitsfeld->result[0]->data->branchen->fixed;
	foreach($taetigkeitsfeld->result[0]->data->branchen->elements as $key=>$elem)
	{
		$branchen_elements[$key]=array('elements'=>array(
			'title'=>$elem->title,
		));
		foreach($elem->elements as $item)
			$branchen_elements[$key]['elements'][]['element']=$item;
	}

	$positionen_fixed = $taetigkeitsfeld->result[0]->data->positionen->fixed;
	foreach($taetigkeitsfeld->result[0]->data->positionen->elements as $key=>$elem)
	{
		$positionen_elements[$key]=array('elements'=>array(
			'title'=>$elem->title,
		));
		foreach($elem->elements as $item)
			$positionen_elements[$key]['elements'][]['element']=$item;
	}

	$aufgaben_fixed = $taetigkeitsfeld->result[0]->data->aufgaben->fixed;
	foreach($taetigkeitsfeld->result[0]->data->aufgaben->elements as $key=>$elem)
	{
		$aufgaben_elements[$key]=array('elements'=>array(
			'title'=>$elem->title,
		));
		foreach($elem->elements as $item)
			$aufgaben_elements[$key]['elements'][]['element']=$item;
	}

}

// Qualifikationsziel
$qualifikationsziel = new qualifikationsziel();
$qualifikationsziel->getAll($studienordnung_id);
if(isset($qualifikationsziel->result[0]))
{
	$qualifikation_bildungsauftrag = $qualifikationsziel->result[0]->data[0]->fixed[0];

	$qualifikation_beschreibung = $qualifikationsziel->result[0]->data[1]->elements[0];
	$qualifikation_kompetenz1 = $qualifikationsziel->result[0]->data[1]->fixed[1];
	$qualifikation_kompetenz2 = $qualifikationsziel->result[0]->data[1]->fixed[2];

	foreach($qualifikationsziel->result[0]->data[1]->elements[1] as $key=>$row_kompetenz)
	{
		$qualifikation_kompetenz1_elements[$key] = array('element'=>$row_kompetenz);
	}

	foreach($qualifikationsziel->result[0]->data[1]->elements[2] as $key=>$row_kompetenz)
	{
		$qualifikation_kompetenz2_elements[$key] = array('element'=>$row_kompetenz);
	}
}
$data = array(
	'studienordnung_id'=>$studienordnung->studienordnung_id,
	'version'=>$studienordnung->version,
	'studiengangbezeichnung'=>$studienordnung->studiengangbezeichnung,
	'studiengangbezeichnung_englisch'=>$studienordnung->studiengangbezeichnung_englisch,
	'studiengangkurzbzlang'=>$studienordnung->studiengangkurzbzlang,
	'akadgrad'=>$akadgrad->titel,
	'organisationsform'=>$orgform->bezeichnung,
	'studiengangstyp'=>$studiengang->typ,
	'studiengangstyp_bezeichnung'=>$studiengang->studiengang_typ_arr[$studiengang->typ],
	'standort'=>$standort->bezeichnung,
	'status_kurzbz'=>$studienordnung->status_kurzbz,
	'studiengang_kz'=>sprintf('%04s',$studiengang->studiengang_kz),
	'status_bezeichnung'=>(isset($status_arr[$studienordnung->status_kurzbz])?$status_arr[$studienordnung->status_kurzbz]:$studienordnung->status_kurzbz),
	'gueltigvon'=>$studienordnung->gueltigvon,
	'gueltigbis'=>$studienordnung->gueltigbis,
	'aenderungsvariante_kurzbz'=>$studienordnung->aenderungsvariante_kurzbz,
	'aenderungsvariante_bezeichnung'=>(isset($av_arr[$studienordnung->aenderungsvariante_kurzbz])?$av_arr[$studienordnung->aenderungsvariante_kurzbz]:$studienordnung->aenderungsvariante_kurzbz),
	'beschluss_studiengang'=>$beschluesse['Studiengang'],
	'beschluss_kollegium'=>$beschluesse['Kollegium'],
	'beschluss_aq'=>$beschluesse['AQ Austria'],
	'begruendung'=>html2odt(json_decode($studienordnung->begruendung)),
	'zugangsvoraussetzung'=>html2odt($zugangsvoraussetzung->result[0]->data),
	'aufnahmeverfahren'=>html2odt($aufnahmeverfahren->result[0]->data),
	'studienplaene'=>$stpl_arr,
	'taetigkeitsfeld_ueberblick'=>html2odt($taetigkeitsfeld_ueberblick),
	'branchen_fixed'=>$branchen_fixed,
	'branchen_elements'=>$branchen_elements,
	'positionen_fixed'=>$positionen_fixed,
	'positionen_elements'=>$positionen_elements,
	'aufgaben_fixed'=>$aufgaben_fixed,
	'aufgaben_elements'=>$aufgaben_elements,
	'qualifikation_bildungsauftrag'=>$qualifikation_bildungsauftrag,
	'qualifikation_beschreibung'=>$qualifikation_beschreibung,
	'qualifikation_kompetenz1'=>$qualifikation_kompetenz1,
	'qualifikation_kompetenz2'=>$qualifikation_kompetenz2,
	'qualifikation_kompetenz1_elements'=>$qualifikation_kompetenz1_elements,
	'qualifikation_kompetenz2_elements'=>$qualifikation_kompetenz2_elements,
);

$files=array();

$doc->addDataArray($data,'studienordnung');
if($output=='xml')
{
	header("Content-type: application/xhtml+xml");
	echo $doc->getXML();
}
else
{
	if(!$doc->create($output))
		die($doc->errormsg);
	$doc->output();
	$doc->close();
}


function printLVTree($tree)
{
	global $semester_summe_sws, $semester_summe_lvs, $lehrform_arr;
	global $output_lvinfo;

	$data = array();
	$i=0;
	foreach($tree as $lv)
	{
		// Nicht studienplanrelevante ueberspringen
		if(!$lv->export)
			continue;

		$semester_summe_sws+=$lv->sws;
		$semester_summe_lvs+=$lv->lvs;

		$data[$i]['lehrveranstaltung']=array(
			'lehrveranstaltung_id'=>$lv->lehrveranstaltung_id,
			'kurzbz'=>$lv->kurzbz,
			'bezeichnung'=>$lv->bezeichnung,
			'bezeichnung_englisch'=>$lv->bezeichnung_english,
			'ects'=>$lv->ects,
			'sws'=>$lv->sws,
			'lvs'=>$lv->lvs,
			'lehrtyp'=>$lv->lehrtyp_kurzbz,
			'lehrform_kurzbz'=>$lv->lehrform_kurzbz,
			'lehrform_bezeichnung'=>(isset($lehrform_arr[$lv->lehrform_kurzbz])?$lehrform_arr[$lv->lehrform_kurzbz]:$lv->lehrform_kurzbz),
			'genehmigung'=>($lv->genehmigung?'true':'false'),
			'pflicht'=>($lv->stpllv_pflicht?'true':'false'),
			'sprache'=>$lv->sprache,
		);

		if($output_lvinfo)
		{
			$lvinfo_found=false;
			$lvinfo = new lvinfo();
			$lvinfo->loadLastLvinfo($lv->lehrveranstaltung_id,true);

			foreach($lvinfo->result as $row_lvinfo)
			{
				if($row_lvinfo->sprache==$lv->sprache)
				{
					$lvinfo_obj = $row_lvinfo;
					$lvinfo_found=true;
					break;
				}
			}

			if($lvinfo_found)
			{


				$lvinfo->load_lvinfo_set($lvinfo_obj->studiensemester_kurzbz);
				$lvinfo_data = array();
				foreach($lvinfo->result as $row_set)
				{
					if($row_set->lvinfo_set_typ=='text')
					{
						$lvinfo_data[$row_set->lvinfo_set_kurzbz]=$lvinfo_obj->data[$row_set->lvinfo_set_kurzbz];
					}
					elseif($row_set->lvinfo_set_typ=='array')
					{
						$lvinfo_data[$row_set->lvinfo_set_kurzbz]['einleitungstext']=$row_set->einleitungstext[$lv->sprache];

						foreach($lvinfo_obj->data[$row_set->lvinfo_set_kurzbz] as $row_lvinfo_element)
							$lvinfo_data[$row_set->lvinfo_set_kurzbz]['elements'][]=array('element'=>$row_lvinfo_element);
					}
				}

				// LV Informationen
				// TODO
				$data[$i]['lehrveranstaltung']['lvinfo']=$lvinfo_data;
			}
		}

		// Darunterliegende LVs/Module
		if(isset($lv->childs) && count($lv->childs)>0)
		{
			$data[$i]['lehrveranstaltung']['childs']=printLVTree($lv->childs);
		}
		$i++;
	}
	return $data;
}

function html2odt($str)
{
	// Word Markup entfernen
	$str=MSClean($str);

	// Line Breaks
	$str = str_replace('<br>','<text:line-break/>',$str);

	// FETT <b>
	$str = str_replace('<b>','<text:span text:style-name="FETT">',$str);
	$str = str_replace('</b>','</text:span>',$str);

	// Kursiv <i>
	$str = str_replace('<i>','<text:span text:style-name="KURSIV">',$str);
	$str = str_replace('</i>','</text:span>',$str);

	// Unterstrichen <u>
	$str = str_replace('<u>','<text:span text:style-name="UNTERSTRICHEN">',$str);
	$str = str_replace('</u>','</text:span>',$str);

	// Durchgestrichen <strike>
	$str = str_replace('<strike>','<text:span text:style-name="DURCHGESTRICHEN">',$str);
	$str = str_replace('</strike>','</text:span>',$str);

	$str = str_replace('<div align="center">','</text:p><text:p text:style-name="ZENTRIERT">',$str);
	$str = str_replace('<div align="left">','</text:p><text:p text:style-name="LINKSBUENDIG">',$str);
	$str = str_replace('<div align="right">','</text:p><text:p text:style-name="RECHTSBUENDIG">',$str);
	$str = str_replace('</div>','</text:p><text:p text:style-name="PNORMAL"> ',$str);

	// Sonstiges
	$str = str_replace('<div align="justify">','</text:p><text:p text:style-name="LINKSBUENDIG">',$str);
	$str = str_replace('<div>','</text:p><text:p text:style-name="LINKSBUENDIG">',$str);

	// Aufzaehlung <ul>
	$str = str_replace('<ul>','</text:p><text:list xml:id="list'.rand().'" text:style-name="LIST_UNORDERED">',$str);
	$str = str_replace('</ul>','</text:list><text:p text:style-name="PNORMAL"> ',$str);

	// Aufzaehlung <ol>
	$str = str_replace('<ol>','</text:p><text:list xml:id="list'.rand().'" text:style-name="LIST_ORDERED">',$str);
	$str = str_replace('</ol>','</text:list><text:p text:style-name="PNORMAL"> ',$str);

	// List item <li>
	$str = str_replace('<li>','<text:list-item><text:p text:style-name="PLIST"><text:span text:style-name="TLIST">',$str);
	$str = str_replace('</li>','</text:span></text:p></text:list-item>',$str);

	$str = str_replace('<font size="4">','<text:span text:style-name="GROESSER">',$str);
	$str = str_replace('</font>','</text:span>',$str);

	$str = str_replace('<blockquote>','</text:p><text:p text:style-name="PEINGERUECKT">',$str);
	$str = str_replace('</blockquote>','</text:p><text:p text:style-name="PNORMAL"> ',$str);

	// Sonstiges
	$str = str_replace('<p>','</text:p><text:p text:style-name="PNORMAL"> ',$str);
	$str = str_replace('</p>','</text:p><text:p text:style-name="PNORMAL"> ',$str);


	return $str;
}

/**
 * MS Markup aus HTML String entfernen bzw durch neutrale Tags ersetzen
 * @param string $str String mit HTML Code aus WYSIWYG Editor
 * @return gesaueberter String
 */
function MSClean($str)
{
	// Kommentar entfernen
	$str = preg_replace('/<!--(.*)-->/Uis', '', $str);

	// <b style="mso-bidi-font-weight:normal"> ... </b> -> <b>...</b>
	$str = preg_replace('/<b style=".*?">(.*?)<\/b>/s','<b>$1</b>',$str);

	$str = preg_replace('/<ul .*?>(.*?)<\/ul>/s','<ul>$1</ul>',$str);
	$str = preg_replace('/<li .*?>(.*?)<\/li>/s','<li>$1</li>',$str);
	$str = preg_replace('/<o:p>(.*?)<\/o:p>/s','$1',$str);


	// <p class="MsoNormal"> ... </p> -> <p>...</p>
	$str = preg_replace('/<p class="MsoNormal">(.*?)<\/p>/s','<p>$1</p>',$str);

	// <p class="MsoNormal" style="...">
	$str = preg_replace('/<p class="MsoNormal" .*?>(.*?)<\/p>/s','<p>$1</p>',$str);

	// <span style="font-family:&quot;Arial Unicode MS&quot;,sans-serif;mso-ascii-font-family:Arial" lang="DE"> -> ...
	$str = preg_replace('/<span .*?>(.*?)<\/span>/s','$1',$str);

	// <span lang="DE"> .. </span> -> ...
	$str = preg_replace('/<span lang="DE">(.*?)<\/span>/s','$1',$str);

	// &nbsp; entfernen
	$str = str_replace('&nbsp;',' ', $str);

	// Tabelle entfernen - kann derzeit nicht dargestellt werden
	$str = preg_replace('/<table .*?>(.*?)<\/table>/s','!! TABELLE WURDE ENTFERNT - Diese kann nicht dargestellt werden !!',$str);
	return $str;
}
?>