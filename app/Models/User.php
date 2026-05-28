<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Plan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'plan', 'settings'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'plan' => 'free',
        'settings' => '[]',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan' => Plan::class,
            'settings' => 'array',
        ];
    }

    public function displayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? $name : (string) $this->email;
    }

    public function initials(): string
    {
        $name = trim((string) $this->name);

        if ($name !== '') {
            $words = preg_split('/\s+/u', $name) ?: [];

            if (count($words) >= 2) {
                $first = mb_substr($words[0], 0, 1);
                $second = mb_substr($words[count($words) - 1], 0, 1);

                return mb_strtoupper($first.$second);
            }

            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        $email = trim((string) $this->email);
        $localPart = $email !== '' ? strstr($email, '@', true) ?: $email : '';

        if ($localPart !== '') {
            return mb_strtoupper(mb_substr($localPart, 0, 2));
        }

        return 'U';
    }
}
