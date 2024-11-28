<?php

/**
 *
 * ed2k. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024, RebeldeMule
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace rbm\ed2k\event;

/**
 * @ignore
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * ed2k Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.modify_text_for_display_after'	=> 'viewtopic_ed2k',
			'core.modify_format_display_text_after'	=> 'posting_preview_ed2k',
		];
	}

	public function humanize_size($size, $rounder = 0)
	{
		$sizes		= array('Bytes', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb');
		$rounders	= array(0, 1, 2, 2, 2, 3, 3, 3, 3);
		$ext		= $sizes[0];
		$rnd		= $rounders[0];

		if ($size < 1024) {
			$rounder	= 0;
			$format		= '%.' . $rounder . 'f Bytes';
		} else {
			for ($i = 1, $cnt = count($sizes); ($i < $cnt && $size >= 1024); $i++) {
				$size	= $size / 1024;
				$ext	= $sizes[$i];
				$rnd	= $rounders[$i];
				$format	= '%.' . $rnd . 'f ' . $ext;
			}
		}

		if (!$rounder) {
			$rounder = $rnd;
		}

		return sprintf($format, round($size, $rounder));
	}
	public function ed2k_link_callback($m)
	{
		$max_len	= 100;
		$href		= 'href="' . $m[2] . '" class="postlink"';
		$fname		= rawurldecode($m[3]);
		$fname		= preg_replace('/&amp;/i', '&', $fname);
		$size		= $this->humanize_size($m[4]);
		//	$size		= $m[4];

		if (strlen($fname) > $max_len) {
			$fname = substr($fname, 0, $max_len - 19) . '...' . substr($fname, -16);
		}
		if (preg_match('#[<>"]#', $fname)) {
			$fname = htmlspecialchars($fname);
		}

		return "<img src='/foro/images/donkey.gif' border='0' title='donkey link' style='vertical-align: text-bottom;' />&nbsp;<a $href>$fname&nbsp;&nbsp;[$size]</a> <a href='http://ed2k.shortypower.org/?hash=$m[5]' target='_blank'><img src='/foro/images/stats.gif' border='0' title='Estadísticas eLink' style='vertical-align: text-bottom;' /></a>";
	}

	public function magnet_callback($mf)
	{
		$magnet_link = 'magnet:' . $mf[1];
		$magnet_rest = str_replace('magnet:?xt=urn:btih:', '', $magnet_link);
		$magnet_troz = explode("&", htmlspecialchars_decode($magnet_rest));
		$magnet_hash = $magnet_troz[0] . '<br />';
		foreach ($magnet_troz as $valores) {
			if (substr($valores, 0, 3) == 'dn=') {
				$magnet_name = urldecode(str_replace('dn=', '', $valores));
			}
			if (substr($valores, 0, 3) == 'xl=') {
				$magnet_size = $this->humanize_size(str_replace('xl=', '', $valores));
			}
		}
		$magnet_size = (empty($magnet_size)) ? '' : '  [' . $magnet_size . ']';
		$magnet_name = (empty($magnet_name)) ? 'Enlace torrent magnético' . $magnet_size : $magnet_name . $magnet_size;
		$magnet_final = "<img src='./images/magnet.gif' alt='Magnet' title='Torrent Magnet'> <a href='$magnet_link'>$magnet_name</a>";
		return $magnet_final;
	}

	public function procesar_ed2k($message)
	{
		$patterns = [
			'#\[url\](ed2k://\|file\|(.*?)\|\d+\|\w+\|(h=\w+\|)?/?)\[/url\]#is',
			'#\[url=(ed2k://\|file\|(.*?)\|\d+\|\w+\|(h=\w+\|)?/?)\](.*?)\[/url\]#is'
		];
		$replacements = [
			'<a href="$1" class="postlink">$2</a>',
			'<a href="$1" class="postlink">$4</a>'
		];
		$message = preg_replace($patterns, $replacements, $message);
		$message = preg_replace_callback("#(^|(?<=[^\w\"']))(ed2k://\|file\|([^\\/\|:<>\*\?\"]+?)\|(\d+?)\|([a-f0-9]{32})\|(.*?)/?)(?![\"'])(?=([,\.]*?[\s<\[])|[,\.]*?$)#i", [$this, 'ed2k_link_callback'], $message);
		$message = preg_replace_callback("#\[url\]magnet:?(\S+)\[/url\]#is", [$this, 'magnet_callback'], $message);
		return $message;
	}

	public function viewtopic_ed2k($event)
	{
		$event['text'] = $this->procesar_ed2k($event['text']);
	}

	public function posting_preview_ed2k($event)
	{
		$event['text'] = $this->procesar_ed2k($event['text']);
	}
}
