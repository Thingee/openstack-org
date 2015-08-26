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
 * Class SummitManager
 */
final class SummitManager {

	/**
	 * @var IEntityRepository
	 */
	private $summit_repository;
	/**
	 * @var ITransactionManager
	 */
	private $tx_manager;
	/**
	 * @var ISummitFactory
	 */
	private $summit_factory;

	/**
	 * @param IEntityRepository   $summit_repository
	 * @param ISummitFactory      $summit_factory
	 * @param ITransactionManager $tx_manager
	 */
	public function __construct(IEntityRepository $summit_repository,
                                ISummitFactory $summit_factory,
	                            ITransactionManager $tx_manager){
		$this->summit_repository = $summit_repository;
		$this->tx_manager        = $tx_manager;
		$this->summit_factory    = $summit_factory;
	}

    /**
     * @param array $data
     * @return ISummit
     */
    public function createSummit(array $data){

        $this_var           = $this;
        $repository         = $this->summit_repository;
        $factory            = $this->summit_factory;

        return  $this->tx_manager->transaction(function() use ($this_var, $factory, $data, $repository){
            $summit = new Summit();
            $summit->registerMainInfo($factory->buildMainInfo($data));

            if ($repository->isDuplicated($summit)) {
                throw new EntityAlreadyExistsException('Summit',sprintf('Name %s',$summit->getName()));
            }

            $repository->add($summit);
            return $summit;
        });
    }

    /**
     * @param $id
     * @return ISummit
     */
    public function deleteSummit($id){
        $repository = $this->summit_repository;

        $summit =  $this->tx_manager->transaction(function() use ($id, $repository){
            $summit = $repository->getById($id);
            if(!$summit)
                throw new NotFoundEntityException('Summit',sprintf('id %s',$id ));

            $repository->delete($summit);
        });
    }

} 