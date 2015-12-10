<?php namespace AgelxNash\modLaravel;

use AgelxNash\modLaravel\Interfaces\Snippet;
use ReflectionClass;

class Modx{
	public function mergeSnippets($content){
		if(strpos($content,'[[')===false) return $content;
		$matches = $this->getTagsFromContent($content,'[[',']]');
		if(!$matches) return $content;
		$i= 0;
		$replace= array ();
		foreach($matches['1'] as $value){
			$replace[$i] = $this->evalSnippet($value);
			$i++;
		}
		$content = str_replace($matches['0'], $replace, $content);

		return $content;
	}

	public function evalSnippet($call){
		$spacer = md5('dummy');
		if(strpos($call,']]>')!==false)
			$call = str_replace(']]>', "]{$spacer}]>",$call);

		$pos['?']  = strpos($call, '?');
		$pos['&']  = strpos($call, '&');
		$pos['=']  = strpos($call, '=');
		$pos['lf'] = strpos($call, "\n");

		if($pos['?'] !== false)
		{
			if($pos['lf']!==false && $pos['?'] < $pos['lf'])
				list($name,$params) = explode('?',$call,2);
			elseif($pos['lf']!==false && $pos['lf'] < $pos['?'])
				list($name,$params) = explode("\n",$call,2);
			else
				list($name,$params) = explode('?',$call,2);
		}
		elseif($pos['&'] !== false && $pos['='] !== false && $pos['?'] === false)
			list($name,$params) = explode('&',$call,2);
		elseif($pos['lf'] !== false)
			list($name,$params) = explode("\n",$call,2);
		else
		{
			$name   = $call;
			$params = '';
		}

		$snip['name']  = trim($name);
		if(strpos($params,$spacer)!==false)
			$params = str_replace("]{$spacer}]>",']]>',$params);
		$params = ltrim($params,"?& \t\n");
		$snip['params'] = &$params;

		//$value = $this->evalSnippet($snip['name'], $this->_snipParamsToArray($snip['params']));
		$value = null;
		$key = '';
		if(empty($params))
		{
			$params = array();
		}
		else
		{
			$_tmp = $params;
			$_tmp = ltrim($_tmp, '?&');
			$params = array();
			while($_tmp!==''):
				$bt = $_tmp;
				$char = substr($_tmp,0,1);
				$_tmp = substr($_tmp,1);
				$doParse = false;

				if($char==='=')
				{
					$_tmp = trim($_tmp);
					$nextchar = substr($_tmp,0,1);
					if(in_array($nextchar, array('"', "'", '`')))
					{
						list($null, $value, $_tmp) = array_pad(explode($nextchar, $_tmp, 3), 3, null);
						if($nextchar !== "'") $doParse = true;
					}
					elseif(strpos($_tmp,'&')!==false)
					{
						list($value, $_tmp) = explode('&', $_tmp, 2);
						$value = trim($value);
						$doParse = true;
					}
					else
					{
						$value = $_tmp;
						$_tmp = '';
					}
				}
				elseif($char==='&')
				{
					$value = '';
				}
				else $key .= $char;

				if(!is_null($value))
				{
					if(strpos($key,'amp;')!==false) $key = str_replace('amp;', '', $key);
					$key=trim($key);
					/*if($doParse)
					{
						if(strpos($value,'[*')!==false) $value = $this->mergeDocumentContent($value);
						if(strpos($value,'[(')!==false) $value = $this->mergeSettingsContent($value);
						if(strpos($value,'{{')!==false) $value = $this->mergeChunkContent($value);
						if(strpos($value,'[[')!==false) $value = $this->evalSnippets($value);
						if(strpos($value,'[+')!==false) $value = $this->mergePlaceholderContent($value);
					}*/
					$params[$key]=$value;

					$key   = '';
					$value = null;
					$_tmp = ltrim($_tmp, " ,\t");
					if(substr($_tmp, 0, 2)==='//') $_tmp = strstr($_tmp, "\n");
				}

				if($_tmp===$bt)
				{
					$key = trim($key);
					if($key!=='') $params[$key] = '';
					break;
				}
			endwhile;
		}
		$snip['params'] = $params;

		return $this->runSnippet($snip['name'], $snip['params']);
	}

	public function runSnippet($name, $params){
		$template = config("modx.snippets.{$name}");
		switch (true) {
			// closure template found
			case is_callable($template):

				return $template($params);

			// filter template found
			case class_exists($template):
				$rc = new ReflectionClass($template);
				return $rc->implementsInterface(Snippet::class) ?  (new $template)->run($params) : '';
			default:
				// template not found
				return '';
				break;
		}
	}
	/**
	 * Parses a resource property string and returns the result as an array
	 *
	 * @param string $propertyString
	 * @return array Associative array in the form property name => property value
	 */
	function parseProperties($propertyString) {
		$parameter= array ();
		if (!empty ($propertyString)) {
			$tmpParams= explode("&", $propertyString);
			for ($x= 0; $x < count($tmpParams); $x++) {
				if (strpos($tmpParams[$x], '=', 0)) {
					$pTmp= explode("=", $tmpParams[$x]);
					$pvTmp= explode(";", trim($pTmp[1]));
					if ($pvTmp[1] == 'list' && $pvTmp[3] != "")
						$parameter[trim($pTmp[0])]= $pvTmp[3]; //list default
					else {
						if($pvTmp[1] == 'list-multi' && $pvTmp[3] != "")
							$parameter[trim($pTmp[0])]= $pvTmp[3]; // list-multi
						else{
							if ($pvTmp[1] != 'list' && $pvTmp[2] != ""){
								$parameter[trim($pTmp[0])]= $pvTmp[2];
							}
						}
					}
				}
			}
		}
		return $parameter;
	}

	/**
	 * Splits a string on a specified character, ignoring escaped content.
	 *
	 * @static
	 * @param string $char A character to split the tag content on.
	 * @param string $str The string to operate on.
	 * @param string $escToken A character used to surround escaped content; all
	 * content within a pair of these tokens will be ignored by the split
	 * operation.
	 * @param integer $limit Limit the number of results. Default is 0 which is
	 * no limit. Note that setting the limit to 1 will only return the content
	 * up to the first instance of the split character and will discard the
	 * remainder of the string.
	 * @return array An array of results from the split operation, or an empty
	 * array.
	 */
	public function escSplit($char, $str, $escToken = '`', $limit = 0) {
		$split= array();
		$charPos = strpos($str, $char);
		if ($charPos !== false) {
			if ($charPos === 0) {
				$searchPos = 1;
				$startPos = 1;
			} else {
				$searchPos = 0;
				$startPos = 0;
			}
			$escOpen = false;
			$strlen = strlen($str);
			for ($i = $startPos; $i <= $strlen; $i++) {
				if ($i == $strlen) {
					$tmp= trim(substr($str, $searchPos));
					if (!empty($tmp)) $split[]= $tmp;
					break;
				}
				if ($str[$i] == $escToken) {
					$escOpen = $escOpen == true ? false : true;
					continue;
				}
				if (!$escOpen && $str[$i] == $char) {
					$tmp= trim(substr($str, $searchPos, $i - $searchPos));
					if (!empty($tmp)) {
						$split[]= $tmp;
						if ($limit > 0 && count($split) >= $limit) {
							break;
						}
					}
					$searchPos = $i + 1;
				}
			}
		} else {
			$split[]= trim($str);
		}
		return $split;
	}

	public function getTagsFromContent($content,$left='[+',$right='+]') {
		$hash = explode($left,$content);
		foreach($hash as $i=>$v) {
			if(0<$i) $hash[$i] = $left.$v;
		}

		$i=0;
		$count = count($hash);
		$safecount = 0;
		$temp_hash = array();
		while(0<$count) {
			$open  = 1;
			$close = 0;
			$safecount++;
			if(1000<$safecount) break;
			while($close < $open && 0 < $count) {
				$safecount++;
				if(!isset($temp_hash[$i])) $temp_hash[$i] = '';
				if(1000<$safecount) break;
				$remain = array_shift($hash);
				$remain = explode($right,$remain);
				foreach($remain as $v)
				{
					if($close < $open)
					{
						$close++;
						$temp_hash[$i] .= $v . $right;
					}
					else break;
				}
				$count = count($hash);
				if(0<$i && strpos($temp_hash[$i],$right)===false) $open++;
			}
			$i++;
		}
		$matches=array();
		$i = 0;
		foreach($temp_hash as $v) {
			if(strpos($v,$left)!==false) {
				$v = substr($v,0,strrpos($v,$right));
				$matches[0][$i] = $v . $right;
				$matches[1][$i] = substr($v,strlen($left));
				$i++;
			}
		}
		return $matches;
	}
}