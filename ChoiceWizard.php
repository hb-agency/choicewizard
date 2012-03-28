<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Winans Creative 2011, Helmut SchottmÙller 2009
 * @author     Blair Winans <blair@winanscreative.com>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Adam Fisher <adam@winanscreative.com>
 * @author     Includes code from survey_ce module from Helmut SchottmÙller <typolight@aurealis.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
 
 
class ChoiceWizard extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Add specific attributes
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'value':
				$this->varValue = deserialize($varValue);
				break;

			case 'mandatory':
				$this->arrConfiguration['mandatory'] = $varValue ? true : false;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/choicewizard/html/choicewizard.js';

		$arrButtons = array('new','copy', 'up', 'down', 'delete');

		$strCommand = 'cmd_' . $this->strField;
		// Change the order
		if ($this->Input->get($strCommand) && is_numeric($this->Input->get('cid')) && $this->Input->get('id') == $this->currentRecord)
		{
			$this->import('Database');

			switch ($this->Input->get($strCommand))
			{
				case 'new':
					array_insert($this->varValue, $this->Input->get('cid') + 1, "");
					break;

				case 'copy':
					$this->varValue = array_duplicate($this->varValue, $this->Input->get('cid'));
					break;

				case 'up':
					$this->varValue = array_move_up($this->varValue, $this->Input->get('cid'));
					break;

				case 'down':
					$this->varValue = array_move_down($this->varValue, $this->Input->get('cid'));
					break;

				case 'delete':
					$this->varValue = array_delete($this->varValue, $this->Input->get('cid'));
					break;
			}

			$this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
						   ->execute(serialize($this->varValue), $this->currentRecord);

			$this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
		}
		
		// Make sure there is at least an empty array
		if (!is_array($this->varValue) || count($this->varValue) == 0)
		{
			$this->varValue = array('answer'=>'', 'text'=>'');
		}

		$wizard = ($this->wizard) ? '<div class="tl_wizard">' . $this->wizard . '</div>' : '';
		// Add label
		$return .= '<div class="tl_multitextwizard">' . $wizard . '
	  <table cellspacing="0" cellpadding="0" class="tl_listwizard" id="ctrl_'.$this->strId.'" summary="Text wizard">';
		$hasTitles = array_key_exists('buttonTitles', $this->arrConfiguration) && is_array($this->arrConfiguration['buttonTitles']);
		// Add input fields
		for ($i=0; $i<count($this->varValue['text']); $i++)
		{
			$return .= '<tr><td style="padding-right: 5px;"><input type="radio" name="'.$this->strId.'[answer]" class="tl_radio" value="'.$i.'"' . $this->optionChecked($this->varValue['answer'], $i) . '" /><td style="padding-right: 5px;"><input type="text" name="'.$this->strId.'[text][]" class="tl_text" value="'.specialchars($this->varValue['text'][$i]).'"' . $this->getAttributes() . ' /></td>';
			$return .= '<td style="white-space:nowrap;">';
			// Add buttons
			foreach ($arrButtons as $button)
			{
				$buttontitle = ($hasTitles && array_key_exists($button, $this->arrConfiguration['buttonTitles'])) ? $this->arrConfiguration['buttonTitles'][$button] : $GLOBALS['TL_LANG'][$this->strTable][$button][0];
				$return .= '<a href="'.$this->addToUrl('&amp;'.$strCommand.'='.$button.'&amp;cid='.$i.'&amp;id='.$this->currentRecord).'" title="'.specialchars($buttontitle).'" onclick="ChoiceWizard.choiceWizard(this, \''.$button.'\', \'ctrl_'.$this->strId.'\'); return false;">'.$this->generateImage($button.'.gif', $buttontitle, 'class="tl_listwizard_img"').'</a> ';
			}
			$return .= '</td></tr>';
		}

		return $return.'
  </table></div>';
	}
}

?>