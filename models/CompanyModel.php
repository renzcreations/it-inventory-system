<?php

namespace Models;

use System\Core\Model;

class CompanyModel extends Model
{
    public function create(string $address, string $email, string $contact, string $createdAt): int
    {
        $this->execute(
            'INSERT INTO company_details (address, email, contact, created_at) VALUES (?, ?, ?, ?)',
            [$address, $email, $contact, $createdAt]
        );
        return $this->lastInsertId();
    }

    public function update(int $id, array $attributes, string $updatedAt): int
    {
        $allowed = ['address', 'email', 'contact'];
        $sets = [];
        $parameters = [];
        foreach ($allowed as $column) {
            if (isset($attributes[$column]) && $attributes[$column] !== '') {
                $sets[] = "{$column} = ?";
                $parameters[] = $attributes[$column];
            }
        }
        $sets[] = 'updated_at = ?';
        $parameters[] = $updatedAt;
        $parameters[] = $id;

        return $this->execute(
            'UPDATE company_details SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $parameters
        );
    }
}
