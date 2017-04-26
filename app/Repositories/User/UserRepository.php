<?php

namespace App\Repositories\User;

use App\User;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\BaseRepository;

/**
 * UserRepository is a class that contains common queries for users
 */
class UserRepository extends BaseRepository implements UserInterface
{

    /**
     * Holds user model
     * @var Model
     */
    protected $userModel;

    /**
     * UserRepository constructor.
     * Inject whatever passed model
     * @param Model $user
     */
    public function __construct(Model $user)
    {
        $this->userModel = $user;
    }

    /**
     * Retrieves user by id
     * @param $id
     * @return Model user
     * @throws \Exception
     */
    public function getUserById($id)
    {
        $user = $this->userModel->find($id);

        if(!$user)
        {
            throw new \Exception(trans('users.no_employee'));
        }
        return $user;
    }

    /**
     * Get All users with role employee
     * @return \App\User[]
     * @throws \Exception
     */
    public function getAllEmployees()
    {
        $employees = $this->userModel->whereHas('roles', function($q){
            $q->where('name','=','employee');
        })->get();

        if(!$employees || $employees->isEmpty())
        {
            throw new \Exception(trans('reports.no_employee'));
        }

        return $employees;
    }

    /**
     * Query scope that gets bonuses for a user
     * @param bool $isAdmin
     * @param Integer $loggedInUserId
     * @param Integer $sentUserId
     * @return mixed
     */
    public function getBonusesForUserScope($isAdmin, $loggedInUserId, $sentUserId)
    {
        //Get bonuses related to a user by userId
        $bonuses = $this->userModel->join('bonuses', 'users.id', 'bonuses.user_id')
            ->select(['bonuses.id', 'bonuses.description', 'bonuses.value', 'bonuses.created_at']);

        //Make sure that user can't see other users data
        if ($isAdmin) {
            $bonuses = $bonuses->where('users.id', $sentUserId);
        } else {
            $bonuses = $bonuses->where('users.id', $loggedInUserId);
        }

        return $bonuses;
    }
    /**
    * Query scope that gets defects for a user
    * @param bool $isAdmin
    * @param Integer $loggedInUserId
    * @param Integer $sentUserId
    * @return mixed
    */
    public function getDefectsForUserScope($isAdmin, $loggedInUserId, $sentUserId)
    {
        //Get defects related to a user by userId
        $defects = $this->userModel->join('defect_user', 'users.id', 'defect_user.user_id')->join('defects', 'defect_user.defect_id', 'defects.id')->select(['defect_user.id', 'defects.title', 'defects.score', 'defect_user.created_at']);

        //Make sure that user can't see other users data
        if ($isAdmin) {
            $defects = $defects->where('users.id', $sentUserId);
        } else {
            $defects = $defects->where('users.id', $loggedInUserId);
        }

        return $defects;
    }

    /**
    * Query gets defects that related to  a user by userId
    * @param Integer $defectAttachmentId
    * @param Integer $userId
    * @return mixed
    */
    public function getDefectsRelatedToUser($defectAttachmentId, $userId)
    {
        //Get defects related to a user by userId
        $userDefects = $this->userModel->with(['defects' => function ($query) use ($defectAttachmentId) {
            $query->where('defect_user.id', $defectAttachmentId);
        }])->where('id', $userId)->first()

        //Didn't get a user
        if (empty($userDefects)) {
            throw new \Exception(trans('users.no_employee'));
        } 
        //Defect id isn't correct
        if (!isset($userDefects->defects[0]->pivot)) {
             throw new \Exception(trans('defects.no_defect'));
        }

        return $userDefects;
    }


     
}