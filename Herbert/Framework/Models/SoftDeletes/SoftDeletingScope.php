<?php namespace Herbert\Framework\Models\SoftDeletes;

use Illuminate\Database\Eloquent\SoftDeletingScope as BaseSoftDeletingScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SoftDeletingScope extends BaseSoftDeletingScope {

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedDeletedAtColumn(), '!=', 'trash');

        $this->extend($builder);
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function remove(Builder $builder, Model $model)
    {
        $column = $model->getQualifiedDeletedAtColumn();

        $query = $builder->getQuery();

        $query->wheres = collect($query->wheres)->reject(function ($where) use ($column) {
            return $this->isSoftDeleteConstraint($where, $column);
        })->values()->all();

        $bindings = $query->getRawBindings();

        foreach ($bindings['where'] as $k => $v)
        {
            if ($v !== 'trash')
            {
                continue;
            }

            unset($bindings['where'][$k]);

            break;
        }

        $query->setBindings($bindings['where']);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);

            return $builder->update([
                $column => 'trash',
            ]);
        });
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->update([$builder->getModel()->getDeletedAtColumn() => 'publish']);
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $this->remove($builder, $model);

            $builder->getQuery()->where($model->getQualifiedDeletedAtColumn(), 'trash');

            return $builder;
        });
    }

    /**
     * Determine if the given where clause is a soft delete constraint.
     *
     * @param  array   $where
     * @param  string  $column
     * @return bool
     */
    protected function isSoftDeleteConstraint(array $where, $column)
    {
        return $where === [
            'type' => 'Basic',
            'column' => $column,
            'operator' => '!=',
            'value' => 'trash',
            'boolean' => 'and'
        ];
    }

}
