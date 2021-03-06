import sqlite3
import json

conn = sqlite3.connect('arktur.db')
c = conn.cursor()

# clean tables

c.execute('''DELETE FROM LEHRVERANSTALTUNG''')
c.execute('''DELETE FROM LEHRVERANSTALTUNG_INFO''')
c.execute('''DELETE FROM MAP_LEHRVERANSTALTUNG_PERSON''')
c.execute('''DELETE FROM LEHRVERANSTALTUNG_RHYTMUS''')
c.execute('''DELETE FROM PRUEFUNG''')
c.execute('''DELETE FROM MAP_PRUEFUNG_LEHRVERANSTALTUNG''')

rythm = {
   "keine Übernahme": 0,
   "Jedes Semester": 1,
   "Jedes 2. Semester": 2
}

files = ['Veranstaltungen_SoSe15', 'Veranstaltungen_WiSe15', 'Veranstaltungen_SoSe16', 'Veranstaltungen_WiSe16', 'Veranstaltungen_SoSe17', 'Veranstaltungen_WiSe17',
        'Veranstaltungen_SoSe18', 'Veranstaltungen_WiSe18', 'Veranstaltungen_SoSe19', 'Veranstaltungen_WiSe19', 'Veranstaltungen_SoSe20', 'Veranstaltungen_WiSe20', 'Veranstaltungen_SoSe21']

i = 0

for rhytmus in rythm:
   c.execute('''INSERT INTO lehrveranstaltung_rhytmus VALUES(?,?)''', [rythm[rhytmus], rhytmus])

for filename in files:
   with open("data/Lehrveranstaltungen/"+filename+".json", encoding='utf8') as file:
      data = json.load(file)

   for elem in data:
      i += 1

      # LEHRVERANSTALTUNG
      elem = data[elem]
      nummer = elem['Veranstaltungsnummer']
      art = elem['Veranstaltungsart']
      rhythmus = rythm[elem['Rhythmus']] if 'Rhythmus' in elem else 0

      c.execute('''INSERT INTO LEHRVERANSTALTUNG (veranstaltungsnummer,art,rhythmus) 
         SELECT ?,?,?
         WHERE NOT EXISTS(
            SELECT 1 FROM lehrveranstaltung WHERE veranstaltungsnummer = ?
         )''', [nummer, art, rhythmus, nummer])

      # LEHRVERANSTALTUNG_INFO
      semester = filename.replace("Veranstaltungen_", "")
      semester += "1" if semester.__contains__("WiSe") else "0"
      semester = semester.replace("SoSe", "20").replace("WiSe", "20")
      titel = elem['Titel']
      frID = elem['FriedolinID']
      aktiv = elem['aktiv']
      sws = elem['SWS'] if 'SWS' in elem else None
      lehrvID = c.execute("SELECT id FROM lehrveranstaltung WHERE veranstaltungsnummer=?",
         [nummer]
      ).fetchall()[0][0]

      c.execute('''INSERT INTO LEHRVERANSTALTUNG_INFO
         VALUES (?,?,?,?,?,?,?)''',
         [i, lehrvID, semester, titel, frID, aktiv, sws]
      )

      # LEHRVERANSTALTUNG_VERANTWORTUNG
      personen = elem['Personen'] if 'Personen' in elem else {}
      verantwortlich = personen['verantwortlich'] if 'verantwortlich' in personen else []
      begleitend = personen['begleitend'] if 'begleitend' in personen else []
      organisatorisch = personen['organisatorisch'] if 'organisatorisch' in personen else []
      rollen = {
         "verantwortlich": verantwortlich,
         "begleitend": begleitend,
         "organisatorisch": organisatorisch
      }

      for rolle in rollen:
         personen = rollen[rolle]

         for person in personen:
            split = person.split(",")
            vorname = split[1].strip() if len(split) > 1 and split[1].strip() != "" else None
            nachname = split[0].strip() if split[0].strip() != "" else None
            grad = split[2].strip() if len(split) > 2 else None

            persID = c.execute("SELECT id FROM person WHERE vorname=? AND nachname=?",
               [vorname, nachname]
            ).fetchall()

            persID = int(persID[0][0]) if len(persID) > 0 else None
            if vorname == "N." and nachname == "N.":
               persID = 0

            c.execute('''INSERT INTO MAP_LEHRVERANSTALTUNG_PERSON 
               VALUES(?,?,?)
            ''', [i, persID, rolle])
      
      # PRUEFUNG

      prüfungen = elem['Prüfungen'] if 'Prüfungen' in elem else []
      
      for prüfung in prüfungen:
         modulcode = prüfung['Modul']
         pnr = prüfung['PNr']
         venr = prüfung['VENr']
         vetitel = prüfung['VETitel']

         c.execute('''INSERT INTO pruefung
         SELECT ?,?,?,?
         WHERE NOT EXISTS(
            SELECT 1 FROM pruefung WHERE venr = ?
         )''', [venr, pnr, modulcode, vetitel, venr])
      
         # PRUEFUNG_LEHRVERANSTALTUNG

         c.execute('''INSERT INTO MAP_PRUEFUNG_LEHRVERANSTALTUNG 
         SELECT ?,?
         WHERE NOT EXISTS(
            SELECT 1 FROM MAP_PRUEFUNG_LEHRVERANSTALTUNG WHERE venr=? AND lehrvInfoID=?
         )''', [venr, i, venr, i])

 #      prüfungen = elem['Prüfungen'] if 'Prüfungen' in elem else []
 #
 #      for prüfung in prüfungen:
 #         modulcode = prüfung['Modul']
 #         pnr = prüfung['PNr']
 #
 #         modulID = c.execute('''SELECT id FROM modul WHERE modulcode=? AND pnr=?''', [modulcode, pnr]).fetchall()
 #
 #         modulID = modulID[0][0] if len(modulID) > 0 else None
 #
 #         if(modulID is None):
 #            if modulcode not in wrongModule:
 #               wrongModule[modulcode] = 1
 #            else:
 #               wrongModule[modulcode] += 1
 #            if modulcode == "BB3.MLS4":
 #               print(modulcode + "\t - " + str(pnr) + "\t" + str(modulID))


      





conn.commit()
conn.close()
