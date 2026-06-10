<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Requests\Team\InviteMemberRequest;
use App\Http\Resources\TeamResource;
use App\Interfaces\TeamRepositoryInterface;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Teams", description="Team management endpoints")
 */
class TeamController extends Controller
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/teams",
     *     summary="List user teams",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of teams")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $teams = $this->teamRepository->getUserTeams($request->user()->id);

        return response()->json(TeamResource::collection($teams)->response()->getData(true));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/teams",
     *     summary="Create a new team",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateTeamRequest")),
     *     @OA\Response(response=201, description="Team created")
     * )
     */
    public function store(CreateTeamRequest $request): JsonResponse
    {
        $team = $this->teamRepository->create($request->validated(), $request->user());

        return response()->json(new TeamResource($team), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/teams/{team}",
     *     summary="Get team details",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Team details"),
     *     @OA\Response(response=403, description="Not a member of this team")
     * )
     */
    public function show(Request $request, Team $team): JsonResponse
    {
        // Authorization: only team members can view the team
        if (! $team->hasMember($request->user()->id)) {
            abort(403, 'You are not a member of this team.');
        }

        return response()->json(new TeamResource($team->load(['owner', 'members', 'projects'])));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/teams/{team}",
     *     summary="Update team info",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Team updated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        // Authorization: only owner/admin can update team
        if (! $team->userCanManage($request->user()->id)) {
            abort(403, 'Only team admins can update team settings.');
        }

        $updated = $this->teamRepository->update($team, $request->validated());

        return response()->json(new TeamResource($updated));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/teams/{team}/members",
     *     summary="Invite a member to team",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Member invited")
     * )
     */
    public function inviteMember(InviteMemberRequest $request, Team $team): JsonResponse
    {
        // Authorization: only admins can invite
        $role = $team->getMemberRole($request->user()->id);
        if (! $role?->canInviteMembers()) {
            abort(403, 'Only admins can invite members.');
        }

        $this->teamRepository->addMember(
            $team,
            $request->validated('user_id'),
            $request->validated('role')
        );

        return response()->json(['message' => 'Member added successfully.']);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/teams/{team}/members/{user}",
     *     summary="Remove a member from team",
     *     tags={"Teams"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Member removed"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function removeMember(Request $request, Team $team, int $userId): JsonResponse
    {
        $role = $team->getMemberRole($request->user()->id);
        if (! $role?->canManageTeam()) {
            abort(403, 'Only admins can remove members.');
        }

        $this->teamRepository->removeMember($team, $userId);

        return response()->json(['message' => 'Member removed successfully.']);
    }
}
