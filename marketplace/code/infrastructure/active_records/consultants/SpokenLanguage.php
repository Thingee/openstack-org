<?php
/**
 * Copyright 2014 Openstack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
/**
 * Class SpokenLanguage
 */
final class SpokenLanguage extends DataObject
implements ISpokenLanguage {

	static $db = array(
		'Name'  => 'Varchar',
	);

	static $indexes = array(
		'Name' => array('type'=>'unique', 'value'=>'Name')
	);

	static $summary_fields = array(
		'Name' => 'Name',
	);

	/**
	 * @return int
	 */
	public function getIdentifier()
	{
		return (int)$this->getField('ID');
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name)
	{
		$this->setField('Name',$name);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getField('Name');
	}

	/**
	 * @return int
	 */
	public function getOrder()
	{
		if($this->hasField('Order'))
			return (int)$this->getField('Order');
		return false;
	}
}