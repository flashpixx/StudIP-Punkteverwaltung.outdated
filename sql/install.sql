CREATE TABLE IF NOT EXISTS `ppv_seminar` (
  `id` varchar(32) NOT NULL,
  `bestehenprozent` decimal(3,2) unsigned NOT NULL,
  `allow_nichtbestanden` int(10) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `ppv_uebung` (
  `seminar` varchar(32) NOT NULL,
  `id` varchar(32) NOT NULL,
  `bestehenprozent` decimal(3,2) unsigned NOT NULL,
  `maxpunkte` int(10) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`seminar`,`id`),
  KEY `seminar` (`seminar`)
);

CREATE TABLE IF NOT EXISTS `ppv_uebungstudent` (
  `uebung` varchar(32) NOT NULL,
  `student` varchar(32) NOT NULL,
  `erreichtepunkte` decimal(3,2) unsigned NOT NULL,
  `zusatzpunkte` decimal(3,2) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`uebung`,`student`)
);

CREATE TABLE IF NOT EXISTS `ppv_uebungstudentlog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uebung` varchar(32) NOT NULL,
  `student` varchar(32) NOT NULL,
  `korrektor` varchar(32) NOT NULL,
  `erreichtepunkte` decimal(3,2) unsigned NOT NULL,
  `zusatzpunkte` decimal(3,2) unsigned NOT NULL,
  `bemerkung` longtext,
  PRIMARY KEY (`id`),
  KEY `uebung` (`uebung`,`student`),
  KEY `korrektor` (`korrektor`)
);
