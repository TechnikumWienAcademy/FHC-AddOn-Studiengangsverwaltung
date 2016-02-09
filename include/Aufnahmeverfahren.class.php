<?php
/* Copyright (C) 2015 fhcomplete.org
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
 * Authors: Stefan Puraner <stefan.puraner@technikum-wien.at>
 */
/**
 * Klasse Tätigkeitsfeld
 * @create 10-01-2007
 */
//require_once('../../../inlcude/basis_db.class.php');
require_once (dirname(__FILE__).'/../../../include/basis_db.class.php');

class aufnahmeverfahren extends basis_db
{
	public $new;			//  boolean
	public $result = array();

	//Tabellenspalten
	public $aufnahmeverfahren_id;//  integer
	public $studienordnung_id;	//  integer
	public $data;	//jsonb
	public $insertamum;		//  timestamp
	public $insertvon;		//  string
	public $updateamum;		//  timestamp
	public $updatevon;		//  string

	/**
	 * Konstruktor
	 * @param $aufnahmeverfahren_id ID der Adresse die geladen werden soll (Default=null)
	 */
	public function __construct($aufnahmeverfahren_id=null)
	{
		parent::__construct();

		if(!is_null($aufnahmeverfahren_id))
			$this->load($aufnahmeverfahren_id);
	}

	/**
	 * Laedt die Tätigkeitsfelder mit der ID $aufnahmeverfahren_id
	 * @param  $aufnahmeverfahren_id ID der zu ladenden Tätigkeitsfelder
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($aufnahmeverfahren_id)
	{
		if(!is_numeric($aufnahmeverfahren_id))
		{
			$this->errormsg = 'aufnahmeverfahren_id ist ungueltig';
			return false;
		}

		$qry = "SELECT * FROM addon.tbl_stgv_aufnahmeverfahren WHERE aufnahmeverfahren_id=".$this->db_add_param($aufnahmeverfahren_id, FHC_INTEGER, false);

		if($this->db_query($qry))
		{
			if($row = $this->db_fetch_object())
			{
				$this->aufnahmeverfahren_id = $row->aufnahmeverfahren_id;
				$this->studienordnung_id = $row->studienordnung_id;
				$this->data = json_decode($row->data);
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				return true;
			}
			else
			{
				$this->errormsg = 'Aufnahmeverfahren existiert nicht';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Aufnahmeverfahren';
			return false;
		}
	}

	/**
	 * Liefert alle Tätigkeitsfelder
	 */
	public function getAll($studienordnung_id=null)
	{
		$qry = "SELECT * FROM addon.tbl_stgv_aufnahmeverfahren ";
		if($studienordnung_id!=null)
			$qry.=" WHERE studienordnung_id=".$this->db_add_param($studienordnung_id);
		$qry.=";";

		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new aufnahmeverfahren();

				$obj->aufnahmeverfahren_id = $row->aufnahmeverfahren_id;
				$obj->studienordnung_id = $row->studienordnung_id;
				$obj->data = json_decode($row->data);
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Aufnahmeverfahren.';
			return false;
		}
	}

	/**
	 * Prueft die Variablen auf Gueltigkeit
	 * @return true wenn ok, false im Fehlerfall
	 */
	private function validate()
	{
		//Zahlenfelder pruefen
		if(!is_numeric($this->studienordnung_id))
		{
			$this->errormsg='studienordnung_id enthaelt ungueltige Zeichen';
			return false;
		}

		$this->errormsg = '';
		return true;
	}

	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $neu auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $reihungstest_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if(!$this->validate())
			return false;

		if($this->new)
		{
			//Neuen Datensatz einfuegen

			$qry='BEGIN; INSERT INTO addon.tbl_stgv_aufnahmeverfahren (studienordnung_id, data, insertamum, insertvon) VALUES('.
			     $this->db_add_param($this->studienordnung_id, FHC_INTEGER).', '.
			     $this->db_add_param($this->data).', now(),'.
			     $this->db_add_param($this->insertvon).');';
		}
		else
		{
			$qry='UPDATE addon.tbl_stgv_aufnahmeverfahren SET '.
				'studienordnung_id='.$this->db_add_param($this->studienordnung_id, FHC_INTEGER).', '.
				'data='.$this->db_add_param($this->data).', '.
				'updateamum= now(), '.
				'updatevon='.$this->db_add_param($this->updatevon).' '.
				'WHERE aufnahmeverfahren_id='.$this->db_add_param($this->aufnahmeverfahren_id, FHC_INTEGER, false).';';
		}
		
		if($this->db_query($qry))
		{
			if($this->new)
			{
				$qry = "SELECT currval('addon.tbl_stgv_aufnahmeverfahren_aufnahmeverfahren_id_seq') as id";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->aufnahmeverfahren_id = $row->id;
						$this->db_query('COMMIT');
						return true;
					}
					else
					{
						$this->errormsg = 'Fehler beim Auslesen der Sequence';
						$this->db_query('ROLLBACK');
						return false;
					}
				}
				else
				{
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					$this->db_query('ROLLBACK');
					return false;
				}
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}
	
	public function delete($aufnahmeverfahren_id)
	{
	    $qry = "DELETE from addon.tbl_stgv_aufnahmeverfahren WHERE aufnahmeverfahren_id=".$this->db_add_param($aufnahmeverfahren_id);
	    
	    if(!$this->db_query($qry))
	    {
		$this->errormsg = 'Fehler beim Löschen der Daten';
		return false;
	    }
	    
	    return true;
	}
}
