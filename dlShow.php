<?php 

/**************************************************************************************************\
 * MISE A JOUR LE
 * ==============
 * 27 Novembre 2010
 *
 *
 * NOTES :
 * ==============
 * Parser RSS permettant la détection (et le téléchargement) des nouveaux épisodes des séries suivies 
 * depuis le site TVU.org.ru, et entre autres gère l'ajout en BDD de leurs noms et nicknames
 *
 * TODO : Gérer ezrss.it
 * 
\**************************************************************************************************/

// Connexion a la base de données 
$link = Mysql::createLink(); 
		
// Récupération des url des flux 
$reqSerie = mysqli_query($link, "SELECT show_id FROM serie");
while ($donneesSerie = mysqli_fetch_row($reqSerie))
{
	// Téléchargement des flux TVU.org.ru	http://tvunderground.org.ru/rss(t).php?se_id=xxxx 
	if (stristr($donneesSerie[0], 'tvu')) {
		$showID = mb_strrichr($donneesSerie[0], '_tvu', TRUE);
		$rssURL = 'http://tvunderground.org.ru/rsst.php?se_id='.$showID;
		$showID .= '_tvu';
	}
	
	// Téléchargement des flux ezRSS.it		http://www.ezrss.it/search/index.php?show_name=xxxx&mode=rss
	elseif (stristr($donnees_serie[0], 'eztv')) {
		$showID = mb_strrichr($donnees_serie[0], '_eztv', TRUE);
		$rssURL = 'http://www.ezrss.it/search/index.php?show_name='.$showID.'&mode=rss';
		$showID .= '_eztv';
	}

	
	/* * * * Récupération des différentes données * * * */
	
	$xml = new SimpleXMLElement($rssURL, NULL, TRUE);
	
	if (stristr($rssURL, 'rsst.php')) { // pour TVU.org.ru
	
		foreach ($xml->channel as $v) { 
		//<title>[torrent] tvunderground.org.ru: Dexter - Season 4 (HDTV) english</title>
			
			$showName = $v->title->asXML();
			$showName = substr($showName, 39);
			$showName = stristr($showName,'-',TRUE);
			$showName = trim($showName); // On trim car il peut contenir un espace au début et a la fin
		}

		foreach ($xml->channel->item as $v) { 
		//<title>[torrent] Dexter - 4x12 - The Getaway</title>     
		//<guid>http://tvunderground.org.ru/torrent.php?tid=2356</guid>
			
			$showEpisode = $v->title->asXML();
			$showEpisode = mb_stristr($showEpisode, ' - ');
			$showEpisode = substr($showEpisode, 3);
			$showEpisode = mb_stristr($showEpisode, ' - ', TRUE);
			$ar_showEpisode[] = $showEpisode;
			
			$tid = $v->guid->asXML();
			$tid = substr($tid, 50, -7);
			$tid .= '_tvu';	
			$ar_tid[] = $tid;
		}
	}
	elseif (stristr($rssURL, 'ezrss.it')) {
	
		foreach ($xml->channel->item as $v) { 
		//<link>http://torrent.zoink.it/Dexter.S05E09.HDTV.XviD-FEVER.[eztv].torrent</link>
		//<category domain="http://eztv.it/shows/78/dexter/"><![CDATA[TV Show / Dexter]]></category>
		//<description><![CDATA[Show Name: Dexter; Episode Title: N/A; Season: 5; Episode: 9]]></description>
		
		$link = $v->link->asXML();
		$link = substr($link, 6, -7);
		$ar_link[] = $link;
		
		$showName = $v->category->asXML();
		$showName = mb_stristr($showName,'/ ');
		$showName = substr($showName, 2,-14);
		
		$ligne = $v->description->asXML();
		$nbSaison = stristr($ligne, 'Season: ');
		$nbSaison = substr($nbSaison, 8);
		$nbSaison = stristr($nbSaison, '; Ep', TRUE);	// On récupère le numéro de saison
						
		$nbEpisode = stristr($ligne,'Episode: ');
		$nbEpisode = substr($nbEpisode, 9);
		$nbEpisode = stristr($nbEpisode, ']]>', TRUE);	// On récupère le numéro de l'épisode
		
		$SaisonEpisode = $nbSaison.'x'.$nbEpisode;
		$ar_SaisonEpisode[] = $SaisonEpisode;
		
		$tid = $v->comments->asXML();
		$tid = stristr($tid,'discuss/'); 
		$tid = substr($tid, 8);
		$tid = stristr($tid, '/</comments>', TRUE);
		echo $tid .= '_eztv';?><br /><?php 		// On récupère l'identifiant "unique" de l'episode
		$ar_tid[] = $tid;
		}
	}
	
	
	/* * * * Traitements des différentes données récupérées * * * */	
	// ! TODO Traitements pour ezrss.it
	
	// Traitement des torrentIDs
		
	if (count($ar_tid) >= 1) {
		asort($ar_tid);
		foreach ($ar_tid as $key => $tid) 
		{
			$req_oldTID = mysqli_query($link, "SELECT old_tid FROM serie WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
		
			while ($donneesTID = mysqli_fetch_row($req_oldTID)) 
			{
				if ($donneesTID[0] != 0) {
					$oldTID = tri_selonSite($donneesTID[0], 1);
				}
				else $oldTID = '0';
				
				$newTID = tri_selonSite($tid, 1); 
			
				if ($newTID > $oldTID) 			// $old et $new sont des entiers (ne comprenant pas '_tvu' / '_eztv') contrairement a $donneesTID[0] et $tid respectivement
				{				
					if (stristr($showID, '_tvu')) {					
						$torURL = 'http://tvunderground.org.ru/torrent.php?tid='.$newTID; // http://tvunderground.org.ru/torrent.php?tid=xxxxxx 	URL Torrent
					}
					elseif (stristr($showID, '_eztv')) {
						// ! TODO Traitement pour ezrss.it
					}
					$tor_local = '/home/floweb/newtorrent/torrent_'.$newTID.'.torrent'; 
					$cmd_tor = '/usr/bin/wget -O '.$tor_local.' '.$torURL;
					exec($cmd_tor);
					mysqli_query($link, "UPDATE serie SET old_tid = '".$tid."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
				}
			}
		}
		unset($ar_tid);
		mysqli_free_result($req_oldTID);
	}
		
		
	// Traitement pour le numéro du dernier épisode téléchargé
		
	if (count($ar_showEpisode) >= 1) {
		asort($ar_showEpisode);
		foreach ($ar_showEpisode as $key => $last_ep) {
			mysqli_query($link, "UPDATE serie SET last_episode = '".$last_ep."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));					
		}
		unset($ar_showEpisode);
	}
		
		
	// Traitement du nom et détermination du nick de la série		
		
	if (stristr($showName, '(')) {
		$nickTemp = stristr($showName, ' (', TRUE);
	}
	else $nickTemp = $showName;
			
	if (stristr($nickTemp, ' ')) {
		$showNick = str_ireplace(' ', '.', $nickTemp);
	}
	else $showNick = $nickTemp;
		
	if (stristr($showNick, "'")) {
		$showNick = str_ireplace("'", "", $showNick);
	}	
	$showNick .= '.';
			
	$showSafe = mysqli_real_escape_string($link, $showName);
	$reqShowName = mysqli_query($link, "SELECT show_name FROM serie WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
			
	while ($donneesShow = mysqli_fetch_row($reqShowName)) 
	{		
		if ($donneesShow[0] != $showName OR $donneesShow[0] == NULL OR $donneesShow[0] == 0)	
		{
			mysqli_query($link, "UPDATE serie SET show_name = '".$showSafe."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
			mysqli_query($link, "UPDATE serie SET show_nick = '".$showNick."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
		}
		elseif ($donneesShow[0] == $showName) {
			$reqSaison = mysqli_query($link, "SELECT last_episode FROM serie WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
	
			while ($donneesSaison = mysqli_fetch_row($reqSaison)) {
				$saison = mb_stristr($donneesSaison[0], 'x', TRUE);
				$showName = $showSafe.' S'.$saison;
			}
			
			mysqli_query($link, "UPDATE serie SET show_name = '".$showName."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
			mysqli_free_result($reqSaison);												
		}
	}
}
mysqli_free_result($reqShowName);
mysqli_free_result($reqSerie);
?>
