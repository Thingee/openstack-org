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
 * Class SapphireRepository
 */
class SapphireRepository extends AbstractEntityRepository
{

	/**
	 * @param IEntity $entity
	 */
	public function __construct(IEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * @param IEntity $entity
	 * @return int|void
	 */
	public function add(IEntity $entity)
	{
		UnitOfWork::getInstance()->scheduleForInsert($entity);
	}

	/**
	 * @param IEntity $entity
	 * @return void
	 */
	public function delete(IEntity $entity)
	{
		UnitOfWork::getInstance()->scheduleForDelete($entity);
	}

	/**
	 * @param int $id
	 * @return IEntity
	 */
	public function getById($id){
		$class  = $this->entity_class;
		$entity = $class::get()->byId($id);
		if (!is_null($entity) && $entity instanceof IEntity) {
			UnitOfWork::getInstance()->setToCache($entity);
            UnitOfWork::getInstance()->scheduleForUpdate($entity);
		}
		return $entity;
	}

	public function getAll(QueryObject $query, $offset = 0, $limit = 10)
	{
		$class = $this->entity_class;
		$query->setBaseEntity(new $class);
		$filter = (string)$query;
		$do = $class::get()->where($filter);

		if (count($query->getAlias()))
			$do = $do->innerJoin($query->getAlias());
		if (count($query->getOrder()))
			$do = $do->sort($query->getOrder());

		$do_limit = $do->limit($limit, $offset);

		if (is_null($do_limit)) return array(array(), 0);
		$res = $do_limit->toArray();
		foreach ($res as $entity) {
			UnitOfWork::getInstance()->setToCache($entity);
			UnitOfWork::getInstance()->scheduleForUpdate($entity);
		}
		return array($res, (int)$do->count());
	}

	public function getBy(QueryObject $query)
	{
		$class  = $this->entity_class;
		$query->setBaseEntity(new $class);
		$filter = (string)$query;

		$do     = $class::get()->where($filter);
        $joins  = $query->getAlias();
        foreach($joins as $table => $clause)
			$do = $do->innerJoin($table, $clause);

		if (count($query->getOrder()))
			$do = $do->sort($query->getOrder());
		if (is_null($do)) return false;
		$entity = $do->first();
		if (!is_null($entity) && $entity instanceof IEntity) {
			UnitOfWork::getInstance()->setToCache($entity);
			UnitOfWork::getInstance()->scheduleForUpdate($entity);
		}
		return $entity;
	}
}