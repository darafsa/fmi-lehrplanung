import sqlite3
import json

# data

with open("data/AlleModule.json") as file:
   data = json.load(file)
with open("data/Personen.txt", encoding='utf8') as file:
	allePersonen = file.readlines()

personen = {
	"N.N.": {
		"id": 0,
		"friedolinID": 0,
		"vorname": None,
		"nachname": None,
		"grad": None
	}
}

for person in allePersonen:
	if person.strip() != "":
		split = person.split(";:;")
		personenVorname = split[0]
		personenNachname = split[1]
		friedolinID = split[2]
		personenDegree = split[3]
		personenName = ""
		if(personenVorname != ""):
			personenName += personenVorname
		if(personenNachname != ""):
			if(personenVorname != ""):
				personenName += " "
			personenName += personenNachname

		if personenVorname.strip() == "":
			personenVorname = None
		if personenNachname.strip() == "":
			personenNachname = None
		if personenDegree.strip() == "":
			personenDegree = None
		if friedolinID.strip() == "":
			friedolinID = None

		personen[personenName] = {
			"id": len(personen),
			"friedolinID": friedolinID,
			"vorname": personenVorname,
			"nachname": personenNachname,
			"grad": personenDegree
		}

modulturnus = {
	"KEINE ANGABE": 0,
	"jedes Semester": 1,
	"jedes 2. Semester (jährlich)": 2,
	"jedes 2. Semester (ab Sommersemester)": 3,
	"jedes 2. Semester (ab Wintersemester)": 4,
	"alle 2 Jahre (ab Sommersemester)": 5,
	"alle 2 Jahre (ab Wintersemester)": 6,
	"jedes 3. Semester": 7,
	"Sommersemester, ggf. auch Wintersemester": 8,
	"Wintersemester, ggf. auch Sommersemester": 9,
	"unregelmäßig, siehe gegebenenfalls zusätzliche Informationen": 10,
	"jedes 3. Wintersemester": 11,
	"jedes 3. Sommersemester": 12
}




conn = sqlite3.connect('arktur.db')
c = conn.cursor()

# clean tables

c.execute('''DELETE FROM MODUL''')
c.execute('''DELETE FROM MODUL_INFO''')
c.execute('''DELETE FROM MAP_MODUL_PERSON''')
c.execute('''DELETE FROM MODUL_TURNUS''')
c.execute('''DELETE FROM PERSON''')
c.execute('''DELETE FROM ABSCHLUSS''')
c.execute('''DELETE FROM FACH''')
c.execute('''DELETE FROM STUDIENGANG''')
c.execute('''DELETE FROM KONTO''')
c.execute('''DELETE FROM MAP_MODUL_KONTO_STUDIENGANG''')

# INSERT MODUL_TURNUS
for elem in modulturnus:
	c.execute('''INSERT INTO MODUL_TURNUS VALUES (
		{id}, 
		"{name}"
	)'''.format(id=modulturnus[elem], name=elem))

# INSERT PERSONEN
i=0
for person in personen:
	person = personen[person]
	c.execute('INSERT INTO PERSON (ID,friedolinID,vorname,nachname,grad) VALUES (?,?,?,?,?)', [i, person['friedolinID'],person['vorname'],person['nachname'],person['grad'],])
	i += 1

i=0
for elem in data:
	i += 1
	# INSERT MODUL
	modulcode = data[elem]['Modulcode']
	c.execute('''INSERT INTO MODUL (modulcode) VALUES (?)''', [modulcode])

	# INSERT MODUL_INFO
	aktivvon = 0
	aktivbis = 1000000
	ects = str(data[elem]['ECTS'])
	praesenzzeit = str(data[elem]['Präsenzzeit'])
	workload = str(data[elem]['Workload'])
	turnus = str(modulturnus[data[elem]['Modulturnus']]) if data[elem]['Modulturnus'] in modulturnus else "0"
	lp = 0
	titel = str(data[elem]['Modultitel'])
	zusammensetzung = str(data[elem]['Zusammensetzung']) if "Zusammensetzung" in data[elem] else None
	vorkenntnisse = str(data[elem]['Vorkenntnisse']) if "Vorkenntnisse" in data[elem] else None
	art = str(data[elem]['Modulart']) if "Modulart" in data[elem] else None
	inhalte = str(data[elem]['Inhalte']) if "Inhalte" in data[elem] else None
	vor_lp = str(data[elem]['Voraussetzung_Leistungspunkte']) if "Voraussetzung_Leistungspunkte" in data[elem] else None
	vor_pruefung = str(data[elem]['Voraussetzung_Modulpruefung']) if "Voraussetzung_Modulpruefung" in data[elem] else None
	vor_zulassung = str(data[elem]['Voraussetzung_Modulzulassung']) if "Voraussetzung_Modulzulassung" in data[elem] else None
	zusatzinfos = str(data[elem]['Zusatzinformationen']) if "Zusatzinformationen" in data[elem] and data[elem]["Zusatzinformationen"] != "" else None
	literatur = str(data[elem]['Literatur']) if "Literatur" in data[elem] else None
	c.execute('INSERT INTO MODUL_INFO VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
		[i,aktivvon,aktivbis,ects,praesenzzeit,workload,turnus,lp,titel,"",zusammensetzung,vorkenntnisse,art,inhalte,vor_lp,vor_pruefung,vor_zulassung,zusatzinfos,literatur]
	)

	# INSERT MODUL_VERANTWORTUNG
	pers = str(data[elem]['Modulverantwortung']) if "Modulverantwortung" in data[elem] else "N.N."
	pers = pers.replace("\n", "").split(", ") if modulcode not in ["FMI-MA3807", "FMI-MA3808"] else [pers]
	for person in pers:
		c.execute('INSERT INTO MAP_MODUL_PERSON VALUES(?, (SELECT id FROM person WHERE vorname is ? and nachname is ? and grad is ?))', [i, personen[person]['vorname'], personen[person]['nachname'], personen[person]['grad']])


# data AlleModuleExtra

with open("data/AlleModuleExtra.json", encoding='utf8') as file:
   data = json.load(file)

for elem in data:
	modulcode = elem['Modulcode']
	pnr = elem['Prüfungsnummer']
	lp = elem['LP']
	titelDE = elem['Modultitel_de']
	titelEN = elem['Modultitel_en']

	c.execute('''UPDATE modul_info SET lp = ?, titel_en = ?
	WHERE modulID=(SELECT ID FROM modul WHERE modulcode = ?)''', [lp, titelEN, modulcode])


# data Abschlüsse

with open("data/Abschlüsse.json", encoding='utf8') as file:
   data = json.load(file)

for elem in data:
	id = elem['id']
	name = elem['Name']
	nameM = elem['Name_Mittel']
	nameK = elem['Name_Kurz']
	
	c.execute('INSERT INTO ABSCHLUSS VALUES (?,?,?,?)',
		[id, name, nameM, nameK]
	)

# data Fächer

with open("data/Fächer.json", encoding='utf8') as file:
   data = json.load(file)

for elem in data:
	id = elem['id']
	name = elem['Name']
	nameK = elem['Name_Kurz']
	
	c.execute('INSERT INTO FACH VALUES (?,?,?)',
		[id, name, nameK]
	)

# data Studiengänge

with open("data/Studiengänge.json", encoding='utf8') as file:
   data = json.load(file)

for elem in data:
	abschluss = elem['Abschluss']
	fach = elem['Fach']
	po = elem['PO-Version']
	name = elem['Name']
	nameK = elem['Name_Kurz']
	von = elem['Aktiv_von']
	bis = elem['Aktiv_bis']
	
	c.execute('INSERT INTO STUDIENGANG (AKTIVVON,AKTIVBIS,abschlussID,fachID,po,name,name_kurz) VALUES (?,?,?,?,?,?,?)',
		[von,bis,abschluss,fach,po,name,nameK]
	)

# data Konten

with open("data/Konten.json", encoding='utf8') as file:
   data = json.load(file)

for elem in data:
	studiengang = elem['Studiengang']
	kontonr = elem['Kontonr']
	mutterkonto = None if elem['Mutterkonto'] == "-1" else elem['Mutterkonto']
	name = elem['Name']
	pflicht = elem['Pflicht']
	
	c.execute('INSERT INTO KONTO (studiengangID,konto_nr,mutterkonto,name,pflicht) VALUES (?,?,?,?,?)',
		[studiengang,kontonr,mutterkonto,name,pflicht]
	)

# data Modulzuordnung

with open("data/Modulzuordnung.json", encoding='utf8') as file:
   data = json.load(file)

for elem in data:
	studiengang = elem['Studiengang']
	konto = elem['Konto']
	modul = elem['Modul']
	
	c.execute('INSERT INTO MAP_MODUL_KONTO_STUDIENGANG VALUES (?,?,?)',
		[studiengang,konto,modul]
	)


conn.commit()
conn.close()