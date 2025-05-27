<?php
namespace App\Filament\Pages\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Component;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use App\Models\Poste;


use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->model(User::class)
                    ->schema([    
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        Grid::make(2)->schema([
                            $this->getRoleFormComponent(), 
                            $this->getPosteFormComponent()->live()->columnSpanFull(),
                        ]),        
                        $this->getSiteFormComponent(),
                        Grid::make(2)->schema([
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent()
                        ])
                    ])
                    ->statePath('data'),
            ),
        ];
    }
 
    protected function getRoleFormComponent(): Component
    {
        return  Hidden::make('role_id')
        // ->relationship(name: 'Role', titleAttribute: 'nomRole')
        ->dehydrated(true)
        ->required();
    }

    protected function getPosteFormComponent(): Component
    {
        return Select::make('poste_id')
            ->relationship(name: 'Poste', titleAttribute: 'nomPoste')
            ->label('Poste')
            ->searchable()
            ->preload()
            ->reactive()
            ->afterStateUpdated(
                function($state, \Filament\Forms\Set $set){
                    $poste = Poste::find($state);
                    switch($poste->nomPoste){
                        case 'Directeur': $set('role_id',2);
                        break;
                        case 'Manager_Operations': $set('role_id',3);
                        break;
                        case 'General_Administrateur': $set('role_id',4);
                        break;
                        case 'Assistant_Admin_Finance': $set('role_id',6);
                        break;

                        default: $set('role_id',5);
                    }
                }
            )
            ;
    }

    protected function getSiteFormComponent(): Component
    {
        return Select::make('site')
            ->multiple()
            ->relationship(titleAttribute: 'nomSite')
            ->preload();
    }
    public function getMaxWidth():string{
        return '3xl';
    }
}