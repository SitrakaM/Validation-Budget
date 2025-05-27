<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class Profile extends Page implements Forms\Contracts\HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.profile';
    protected static ?string $navigationGroup = 'Paramètres'; // facultatif

    public $name;
    public $email;

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('email')->email()->required(),
        ];
    }

    public function save()
    {
        $data = $this->form->getState();
        $user = Auth::user();
        $user->update($data);

        Notification::make()
        ->title('Profil mis à jour')
        ->success()
        ->send();    }
}
