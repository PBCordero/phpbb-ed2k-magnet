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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	protected $language;
	protected $icon_url;

	public function __construct(\phpbb\language\language $language)
	{
		$this->language = $language;
		$this->icon_url = "/foro/ext/rbm/ed2k/styles/all/theme/images/";
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.modify_text_for_display_after'    => 'viewtopic_ed2k',
			'core.modify_format_display_text_after' => 'posting_preview_ed2k',
		];
	}

	private function humanize_size($size, $rounder = 0)
	{
		$sizes = ['Bytes', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb'];
		$rounders = [0, 1, 2, 2, 2, 3, 3, 3, 3];
		$i = 0;
		while ($size >= 1024 && $i < count($sizes) - 1) {
			$size /= 1024;
			$i++;
		}
		$rounder = $rounder ?: $rounders[$i];
		return sprintf('%.'.$rounder.'f %s', round($size, $rounder), $sizes[$i]);
	}

	private function ed2k_link_callback($m)
	{
		$max_len = 100;
		$href = 'href="' . $m[2] . '" class="postlink"';
		$fname = htmlspecialchars(rawurldecode($m[3]));
		$size = $this->humanize_size($m[4]);
		if (strlen($fname) > $max_len) {
			$fname = substr($fname, 0, $max_len - 19) . '...' . substr($fname, -16);
		}
		$raw = htmlspecialchars($m[2]);
		$checkbox = "<input type='checkbox' class='ed2k-magnet-checkbox' data-raw=\"$raw\" />";
		return "$checkbox <img src='{$this->icon_url}donkey.gif' border='0' title='donkey link' style='vertical-align: text-bottom;' />&nbsp;<a $href>$fname&nbsp;&nbsp;[$size]</a> <a href='http://ed2k.shortypower.org/?hash=$m[5]' target='_blank'><img src='{$this->icon_url}stats.gif' border='0' title='Estadísticas eLink' style='vertical-align: text-bottom;' /></a>";
	}

	private function magnet_callback($mf)
	{
		$magnet_link = 'magnet:' . $mf[1];
		$magnet_rest = str_replace('magnet:?xt=urn:btih:', '', $magnet_link);
		$magnet_troz = explode("&", htmlspecialchars_decode($magnet_rest));
		$magnet_name = '';
		$magnet_size = '';
		foreach ($magnet_troz as $valores) {
			if (strpos($valores, 'dn=') === 0) {
				$magnet_name = urldecode(substr($valores, 3));
			}
			if (strpos($valores, 'xl=') === 0) {
				$magnet_size = $this->humanize_size(substr($valores, 3));
			}
		}
		$magnet_size = $magnet_size ? "  [$magnet_size]" : '';
		$magnet_name = $magnet_name ? $magnet_name . $magnet_size : 'Enlace torrent magnético' . $magnet_size;
		$raw = htmlspecialchars($magnet_link);
		$checkbox = "<input type='checkbox' class='ed2k-magnet-checkbox' data-raw=\"$raw\" />";
		return "$checkbox <img src='{$this->icon_url}magnet.gif' alt='Magnet' title='Torrent Magnet'> <a href='$magnet_link'>$magnet_name</a>";
	}

	private $msg_counter = 0; // Para IDs únicos por mensaje

	private function procesar_ed2k($message)
	{
		$this->msg_counter++;
		$msg_id = 'ed2k-magnet-msg-' . $this->msg_counter;

		$patterns = [
			'#\[url\](ed2k://\|file\|(.*?)\|\d+\|\w+\|(h=\w+\|)?/?)\[/url\]#is',
			'#\[url=(ed2k://\|file\|(.*?)\|\d+\|\w+\|(h=\w+\|)?/?)\](.*?)\[/url\]#is'
		];
		$replacements = [
			'<a href="$1" class="postlink">$2</a>',
			'<a href="$1" class="postlink">$4</a>'
		];
		$message = preg_replace($patterns, $replacements, $message);
		$message = preg_replace_callback(
			"#(^|(?<=[^\w\"']))(ed2k://\|file\|([^\\/\|:<>\*\?\"]+?)\|(\d+?)\|([a-f0-9]{32})\|(.*?)/?)(?![\"'])(?=([,\.]*?[\s<\[])|[,\.]*?$)#i",
			[$this, 'ed2k_link_callback'],
			$message
		);
		$message = preg_replace_callback(
			"#\[url\]magnet:?(\S+)\[/url\]#is",
			[$this, 'magnet_callback'],
			$message
		);
		// $message .= '<div>PATATAS</div>';
		return $message;
	}

	public function viewtopic_ed2k($event)
	{
	// print_r($event);
		$event['text'] = $this->procesar_ed2k($event['text']);
	}

	public function posting_preview_ed2k($event)
	{
		$event['text'] = $this->procesar_ed2k($event['text']);
	}
}
