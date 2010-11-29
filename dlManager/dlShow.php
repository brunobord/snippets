<?php 

/**************************************************************************************************\
 * MISE A JOUR LE
 * ==============
 * 28 Novembre 2010
 *
 *
 * NOTES :
 * ==============
 * "Robot" permettant la détection (et le téléchargement) des nouveaux épisodes des séries suivies 
 * depuis le site TVU.org.ru, et entre autres gère l'ajout en BDD de leurs noms et nicknames
 *
 * ! TODO : Vérifier si la gestion ezrss.it est "vraiment" bugless
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
	elseif (stristr($donnees_serie[0], 'ezrss')) {
		$showID = mb_strrichr($donnees_serie[0], '_ezrss', TRUE);
		$rssURL = 'http://www.ezrss.it/search/index.php?show_name='.$showID.'&mode=rss';
		$showID .= '_ezrss';
	}

	
	/* * * * Récupération des différentes données * * * */
	
	$xml = new SimpleXMLElement($rssURL, NULL, TRUE);
	
	if (stristr($rssURL, 'rsst.php')) { // pour TVU.org.ru
	
		foreach ($xml->channel as $v) { 
		//<title>[torrent] tvunderground.org.ru: Dexter - Season 4 (HDTV) english</title>
			
			$showName = $v->title->asXML();
			$showName = substr($showName, 39);
			$showName = stristr($showName,'-',TRUE);
			$showName = trim($showName); // On récupère le nom de la série
		}

		foreach ($xml->channel->item as $v) { 
		//<title>[torrent] Dexter - 4x12 - The Getaway</title>     
		//<guid>http://tvunderground.org.ru/torrent.php?tid=2356</guid>
			
			$SaisonEpisode = $v->title->asXML();
			$SaisonEpisode = mb_stristr($SaisonEpisode, ' - ');
			$SaisonEpisode = substr($SaisonEpisode, 3);
			$SaisonEpisode = mb_stristr($SaisonEpisode, ' - ', TRUE);
			$ar_SaisonEpisode[] = $SaisonEpisode;	// On récupère le numéro de la saison et de l'episode
			
			$tid = $v->guid->asXML();
			$tid = substr($tid, 50, -7);
			$tid .= '_tvu';	
			$ar_tid[] = $tid;	// On récupère l'identifiant du torrent de l'episode, qui sert aussi d'ID "unique" de l'episode
		}
	}
	elseif (stristr($rssURL, 'ezrss.it')) {
	
		foreach ($xml->channel->item as $v) { 
		//<link>http://torrent.zoink.it/Dexter.S05E09.HDTV.XviD-FEVER.[eztv].torrent</link>
		//<category domain="http://eztv.it/shows/78/dexter/"><![CDATA[TV Show / Dexter]]></category>
		//<description><![CDATA[Show Name: Dexter; Episode Title: N/A; Season: 5; Episode: 9]]></description>
		//<comments>http://eztv.it/forum/discuss/24173/</comments>
		
			$url = $v->link->asXML();
			$url = substr($url, 6, -7);
			$ar_url[] = $url;		// On récupère le lien du torrent de l'episode
		
			$showName = $v->category->asXML();
			$showName = mb_stristr($showName,'/ ');
			$showName = substr($showName, 2,-14);	// On récupère le nom de la série
		
			$ligne = $v->description->asXML();
			$nbSaison = stristr($ligne, 'Season: ');
			$nbSaison = substr($nbSaison, 8);
			$nbSaison = stristr($nbSaison, '; Ep', TRUE);	// On récupère le numéro de la saison
						
			$nbEpisode = stristr($ligne,'Episode: ');
			$nbEpisode = substr($nbEpisode, 9);
			$nbEpisode = stristr($nbEpisode, ']]>', TRUE);	// On récupère le numéro de l'episode
		
			$SaisonEpisode = $nbSaison.'x'.$nbEpisode;
			$ar_SaisonEpisode[] = $SaisonEpisode;
		
			$tid = $v->comments->asXML();
			$tid = stristr($tid,'discuss/'); 
			$tid = substr($tid, 8);
			$tid = stristr($tid, '/</comments>', TRUE);
			$tid .= '_ezrss';								// On récupère l'identifiant "unique" de l'episode
			if ($tid != '_ezrss') {
				$ar_tid[] = $tid;
			}
		}
	}
	
	
	/* * * * Traitements des différentes données récupérées * * * */	
	
	// Traitement des torrentIDs
		
	if (count($ar_tid) >= 1) {
		asort($ar_tid);
		foreach ($ar_tid as $key => $tid) 
		{
			$req_oldTID = mysqli_query($link, "SELECT old_tid FROM serie WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
		
			while ($donneesTID = mysqli_fetch_row($req_oldTID)) 
			{
				if ($donneesTID[0] != 0) {
					$oldTID = tri_selonSite($donneesTID[0]);
				}
				else $oldTID = '0';
				
				$newTID = tri_selonSite($tid); 
			
				if ($newTID > $oldTID) 			// $oldTID et $newTID sont des entiers (ne comprenant pas '_tvu' / '_ezrss') contrairement a $donneesTID[0] et $tid respectivement
				{				
					if (stristr($showID, '_tvu')) {					
						$torURL = 'http://tvunderground.org.ru/torrent.php?tid='.$newTID; // http://tvunderground.org.ru/torrent.php?tid=xxxxxx 	URL Torrent
						$torLocal = '/home/floweb/newtorrent/torrent_'.$newTID.'.torrent'; 
						$cmdTor = '/usr/bin/wget -O '.$torLocal.' '.$torURL;
						exec($cmdTor);
					}
					elseif (stristr($showID, '_ezrss')) {
						asort($ar_url);
						foreach ($ar_url as $key => $torURL) {
							$torLocal = '/home/floweb/newtorrent/torrent_'.$newTID.'.torrent'; 
							$cmdTor = '/usr/bin/wget -O '.$torLocal.' '.$torURL;
							exec($cmdTor);
							unset($ar_url[$torURL]);
							break;
						}
					}
					mysqli_query($link, "UPDATE serie SET old_tid = '".$tid."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));
				}
			}
		}
		unset($ar_tid);
		mysqli_free_result($req_oldTID);
	}
		
		
	// Traitement pour le numéro du dernier épisode téléchargé
		
	if (count($ar_SaisonEpisode) >= 1) {
		asort($ar_SaisonEpisode);
		foreach ($ar_SaisonEpisode as $key => $lastEp) {
			mysqli_query($link, "UPDATE serie SET last_episode = '".$lastEp."' WHERE show_id = '".$showID."'") or exit(mysqli_error($link));					
		}
		unset($ar_SaisonEpisode);
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
