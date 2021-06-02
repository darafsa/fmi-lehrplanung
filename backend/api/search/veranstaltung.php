<?php

class veranstaltung {
	public static function searchAll($titel, $limit) {
		$db = connectDatabase();
		$allSemester = array();
		$answer = array();

		$ret = $db->fetchData(<<<EOF
			SELECT semester
			FROM Lehrveranstaltung_Info
			WHERE titel LIKE '%$titel%'
			GROUP BY semester
			ORDER BY semester DESC
		EOF);

		while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
			array_push($allSemester, $row['semester']);
		}

		$i = $limit;
		foreach ($allSemester as &$sem) {
			if ($i != 0) {
				if ($sem % 10 == 0) {
					$semStr = "SoSe " . (int)($sem / 10);
				} else {
					$semStr = "WiSe " . (int)($sem / 10);
				}
				$answer["data"]["$semStr"] = array();
			}
			$ret = $db->fetchData(<<<EOF
				SELECT veranstaltungsnummer nr, titel, semester, aktiv
				FROM lehrveranstaltung_info
				WHERE titel LIKE '%$titel%' AND semester=$sem
				LIMIT $i
			EOF);
			while (($row = $ret->fetchArray(SQLITE3_ASSOC)) && $i != 0) {
				$i--;
				array_push($answer["data"]["$semStr"], $row);
			}
		}

		$answer['count'] = $limit - $i;

		header('Content-Type: application/json');
		echo (json_encode($answer, true));

		$db->close();
	}

	public static function search($vnr, $semester) {
		$db = connectDatabase();
		$answer = array();
		$exams = array();
		$rolls = array('verantwortlich', 'begleitend', 'organisatorisch');

		$ret = $db->fetchData(<<<EOF
			SELECT inf.titel, inf.veranstaltungsnummer, semester, friedolinID, aktiv, sws, name turnus, art
			FROM Lehrveranstaltung_Info inf
			JOIN Lehrveranstaltung l ON inf.veranstaltungsnummer=l.veranstaltungsnummer 
			JOIN Lehrveranstaltung_Rhytmus r ON l.rhythmusID=r.rhythmusID
			WHERE inf.veranstaltungsnummer=$vnr AND inf.semester=$semester
		EOF);

		while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
			$answer['data'] = $row;
		}

		$ret = $db->fetchData(<<<EOF
			SELECT kommentar Kommentar, literatur Literatur, bemerkung Bemerkung, zielgruppe Zielgruppe, lerninhalte Lerninhalte, leistungsnachweis Leistungsnachweis
			FROM Lehrveranstaltung_Info inf
			JOIN Lehrveranstaltung_Inhalt inh ON inf.lehrvID=inh.lehrvID
			WHERE inf.veranstaltungsnummer=$vnr AND inf.semester=$semester
		EOF);

		while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
			$answer['content'] = $row;
		}

		foreach ($rolls as &$roll) {
			$ret = $db->fetchData(<<<EOF
				SELECT p.vorname, p.nachname, p.grad, blp.rolle, p.friedolinID
				FROM Lehrveranstaltung_Info i
				JOIN BRIDGE_Lehrveranstaltung_Person blp, Person p ON blp.lehrvID=i.lehrvID AND blp.personenID=p.personenID
				WHERE i.veranstaltungsnummer=$vnr AND i.semester=$semester AND blp.rolle='$roll'
			EOF);

			$people = array();

			while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
				array_push($people, $row);
			}

			$roll = ucfirst($roll);
			$answer["people"]["$roll"] = $people;
		}

		$ret = $db->fetchData(<<<EOF
			SELECT pnr, modulcode, pr.titel
			FROM Lehrveranstaltung_Info i
			JOIN BRIDGE_Lehrveranstaltung_Pruefung blp, Pruefung pr ON blp.lehrvID=i.lehrvID AND blp.VENR=pr.VENR
			WHERE i.veranstaltungsnummer=$vnr AND i.semester=$semester
		EOF);

		while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
			array_push($exams, $row);
		}

		$answer['exams'] = $exams;

		header('Content-Type: application/json');
		echo (json_encode($answer, true));

		$db->close();
	}
}
