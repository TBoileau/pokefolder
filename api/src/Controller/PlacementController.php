<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\BinderSlotFace;
use App\Exception\Binder\BinderNotFoundException;
use App\Exception\Binder\OwnedCardAlreadyPlacedException;
use App\Exception\Binder\OwnedCardNotFoundException;
use App\Exception\Binder\OwnedCardNotPlacedException;
use App\Exception\Binder\PositionOutOfBoundsException;
use App\Exception\Binder\SlotAlreadyOccupiedException;
use App\UseCase\Binder\MoveCard\Handler as MoveCardHandler;
use App\UseCase\Binder\MoveCard\Input as MoveCardInput;
use App\UseCase\Binder\PlaceCard\Handler as PlaceCardHandler;
use App\UseCase\Binder\PlaceCard\Input as PlaceCardInput;
use App\UseCase\Binder\SuggestPlacement\Handler as SuggestPlacementHandler;
use App\UseCase\Binder\SuggestPlacement\Input as SuggestPlacementInput;
use App\UseCase\Binder\UnplaceCard\Handler as UnplaceCardHandler;
use App\UseCase\Binder\UnplaceCard\Input as UnplaceCardInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function is_array;
use function is_int;
use function is_string;

/**
 * HTTP entry point for binder placement actions. Translates JSON bodies
 * into the relevant UseCase Input objects and maps domain exceptions to
 * HTTP status codes.
 */
final readonly class PlacementController
{
    public function __construct(
        private PlaceCardHandler $placeCardHandler,
        private SuggestPlacementHandler $suggestPlacementHandler,
        private UnplaceCardHandler $unplaceCardHandler,
        private MoveCardHandler $moveCardHandler,
    ) {
    }

    #[Route(
        path: '/api/owned-cards/{id}/move',
        name: 'app_owned_card_move',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['POST'],
    )]
    public function move(string $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $binderId = $payload['binderId'] ?? null;
        $pageNumber = $payload['pageNumber'] ?? null;
        $faceRaw = $payload['face'] ?? null;
        $row = $payload['row'] ?? null;
        $col = $payload['col'] ?? null;

        if (!is_string($binderId) || !is_int($pageNumber) || !is_string($faceRaw) || !is_int($row) || !is_int($col)) {
            return new JsonResponse(
                ['error' => 'Expected binderId (string), pageNumber (int), face (string), row (int), col (int).'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $face = BinderSlotFace::tryFrom($faceRaw);
        if (!$face instanceof BinderSlotFace) {
            return new JsonResponse(
                ['error' => 'face must be "recto" or "verso".'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $input = new MoveCardInput(
            ownedCardId: $id,
            binderId: $binderId,
            pageNumber: $pageNumber,
            face: $face,
            row: $row,
            col: $col,
        );

        try {
            $output = ($this->moveCardHandler)($input);
        } catch (BinderNotFoundException|OwnedCardNotFoundException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (PositionOutOfBoundsException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (SlotAlreadyOccupiedException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'slotId' => $output->slotId,
            'binderId' => $output->binderId,
            'ownedCardId' => $output->ownedCardId,
            'pageNumber' => $output->pageNumber,
            'face' => $output->face->value,
            'row' => $output->row,
            'col' => $output->col,
        ], Response::HTTP_OK);
    }

    #[Route(
        path: '/api/owned-cards/{id}/unplace',
        name: 'app_owned_card_unplace',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['POST'],
    )]
    public function unplace(string $id): JsonResponse
    {
        try {
            ($this->unplaceCardHandler)(new UnplaceCardInput($id));
        } catch (OwnedCardNotFoundException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (OwnedCardNotPlacedException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        path: '/api/owned-cards/{id}/suggest-placement',
        name: 'app_owned_card_suggest_placement',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['POST'],
    )]
    public function suggestPlacement(string $id): JsonResponse
    {
        try {
            $output = ($this->suggestPlacementHandler)(new SuggestPlacementInput($id));
        } catch (OwnedCardNotFoundException $ownedCardNotFoundException) {
            return new JsonResponse(['error' => $ownedCardNotFoundException->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['binderId' => $output->binderId], Response::HTTP_OK);
    }

    #[Route(
        path: '/api/binders/{id}/place',
        name: 'app_binder_place_card',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['POST'],
    )]
    public function place(string $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $ownedCardId = $payload['ownedCardId'] ?? null;
        $pageNumber = $payload['pageNumber'] ?? null;
        $faceRaw = $payload['face'] ?? null;
        $row = $payload['row'] ?? null;
        $col = $payload['col'] ?? null;

        if (!is_string($ownedCardId) || !is_int($pageNumber) || !is_string($faceRaw) || !is_int($row) || !is_int($col)) {
            return new JsonResponse(
                ['error' => 'Expected ownedCardId (string), pageNumber (int), face (string), row (int), col (int).'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $face = BinderSlotFace::tryFrom($faceRaw);
        if (!$face instanceof BinderSlotFace) {
            return new JsonResponse(
                ['error' => 'face must be "recto" or "verso".'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $input = new PlaceCardInput(
            binderId: $id,
            ownedCardId: $ownedCardId,
            pageNumber: $pageNumber,
            face: $face,
            row: $row,
            col: $col,
        );

        try {
            $output = ($this->placeCardHandler)($input);
        } catch (BinderNotFoundException|OwnedCardNotFoundException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (PositionOutOfBoundsException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (OwnedCardAlreadyPlacedException|SlotAlreadyOccupiedException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'slotId' => $output->slotId,
            'binderId' => $output->binderId,
            'ownedCardId' => $output->ownedCardId,
            'pageNumber' => $output->pageNumber,
            'face' => $output->face->value,
            'row' => $output->row,
            'col' => $output->col,
        ], Response::HTTP_CREATED);
    }
}
