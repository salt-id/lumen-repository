<?php

namespace SaltId\LumenRepository\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface RepositoryCriteriaInterface
{
    /**
     * Apply criteria in current Query
     *
     * @return static
     */
    public function applyCriteria(): static;

    /**
     * Push Criteria for filter the query
     *
     * @param CriteriaInterface $criteria
     *
     * @return static
     */
    public function pushCriteria(CriteriaInterface $criteria): static;

    /**
     * Pop Criteria
     *
     * @param CriteriaInterface $criteria
     *
     * @return static
     */
    public function popCriteria(CriteriaInterface $criteria): static;

    /**
     * Get Collection of Criteria
     *
     * @return Collection
     */
    public function getCriteria(): Collection;

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     *
     */
    public function getByCriteria(CriteriaInterface $criteria);

    /**
     * Skip Criteria
     *
     * @param bool $status
     *
     * @return static
     */
    public function skipCriteria(bool $status = true): static;

    /**
     * Reset all Criteria
     *
     * @return static
     */
    public function resetCriteria(): static;
}
