<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
    ];

    /**
     * Deletes the organization and all associated data.
     *
     * This function removes the association of the organization for all users,
     * and then deletes the organization and its associated data.
     *
     * @return void
     */
    public function delete()
    {
        // Rimuovi l'associazione dell'organizzazione per tutti gli utenti
        $this->members()->detach();

        // Elimina l'organizzazione e i suoi dati associati
        parent::delete();
    }

    public function settings()
    {
        return $this->hasOne(OrganizationSetting::class);
    }

    public function getSetting($key, $default = null)
    {
        return $this->settings ? $this->settings->$key : $default;
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(Hosting::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function recurrent_expenses(): HasMany
    {
        return $this->hasMany(RecurrentExpense::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }

    public function time_entries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
