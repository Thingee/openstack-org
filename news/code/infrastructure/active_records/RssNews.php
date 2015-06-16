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
 * Class EventRegistrationRequest
 */
final class RssNews
    extends DataObject
    implements IEntity
{

    static $create_table_options = array('MySQLDatabase' => 'ENGINE=InnoDB');

    static $db = array(
        'Date'  => 'Datetime',
        'Headline' => 'Varchar(512)',
        'Link' => 'Varchar(255)',
        'Category' => 'Varchar(255)',
    );

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return (int)$this->getField('ID');
    }
}