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
 * Class SapphireJobRepository
 */
final class SapphireJobRepository extends SapphireRepository {

	public function __construct(){
		parent::__construct(new JobPage);
	}

	public function delete(IEntity $entity){
		$entity->clearLocations();
		parent::delete($entity);
	}

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getAllPosted($offset = 0, $limit = 10)	{
        $query = new QueryObject();
        $query->addAndCondition(QueryCriteria::equal('Active', 1));
        $query->addAndCondition(QueryCriteria::greater('ExpirationDate', date('Y-m-d')));
        $query->addOrder(QueryOrder::desc('JobPostedDate'));
        return  $this->getAll($query,$offset,$limit);
    }
} 