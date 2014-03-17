CREATE TABLE IF NOT EXISTS `ppv_seminar` (
  `id` varchar(32) NOT NULL,
  `close` datetime DEFAULT NULL,
  `bestandenprozent` decimal(5,2) unsigned NOT NULL,
  `allow_nichtbestanden` int(10) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `ppv_seminarmanuellezulassung` (
  `seminar` varchar(32) NOT NULL,
  `student` varchar(32) NOT NULL,
  `bemerkung` longtext NOT NULL,
  PRIMARY KEY (`seminar`, `student`)
);

CREATE TABLE IF NOT EXISTS `ppv_uebung` (
  `seminar` varchar(32) NOT NULL,
  `id` varchar(32) NOT NULL,
  `uebungsname` varchar(200) NOT NULL,
  `bestandenprozent` decimal(5,2) unsigned NOT NULL,
  `maxpunkte` int(10) unsigned NOT NULL,
  `abgabe` datetime DEFAULT NULL,
  `digitaleabgabeplagiaturl` longtext,
  'digitaleabgabebewertungurl' longtext,
  `bemerkung` longtext,
  PRIMARY KEY (`seminar`,`id`),
  UNIQUE KEY `uebung` (`seminar`,`uebungsname`),
  KEY `seminar` (`seminar`)
);

CREATE TABLE IF NOT EXISTS `ppv_uebungstudent` (
  `uebung` varchar(32) NOT NULL,
  `student` varchar(32) NOT NULL,
  `korrektor` varchar(32) NOT NULL,
  `erreichtepunkte` decimal(5,2) unsigned NOT NULL,
  `zusatzpunkte` decimal(5,2) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`uebung`,`student`)
);

CREATE TABLE IF NOT EXISTS `ppv_studiengang` (
  `seminar` varchar(32) NOT NULL,
  `student` varchar(32) NOT NULL,
  `abschluss` varchar(32) NOT NULL,
  `studiengang` varchar(32) NOT NULL,
  PRIMARY KEY (`seminar`,`student`),
  KEY `abschluss` (`abschluss`),
  KEY `studiengang` (`studiengang`)
);

CREATE TABLE IF NOT EXISTS `ppv_bonuspunkte` (
  `seminar` varchar(32) NOT NULL,
  `prozent` decimal(5,2) unsigned NOT NULL,
  `punkte` decimal(5,2) unsigned NOT NULL,
  PRIMARY KEY (`seminar`,`prozent`)
);

CREATE TABLE IF NOT EXISTS `ppv_uebungstudentlog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uebung` varchar(32) NOT NULL,
  `student` varchar(32) NOT NULL,
  `korrektor` varchar(32) NOT NULL,
  `erreichtepunkte` decimal(5,2) unsigned NOT NULL,
  `zusatzpunkte` decimal(5,2) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`id`),
  KEY `uebung` (`uebung`,`student`),
  KEY `korrektor` (`korrektor`)
);
