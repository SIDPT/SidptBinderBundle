<?php

namespace Sidpt\BinderBundle\API\Controller;

// traits
use Claroline\AppBundle\Controller\RequestDecoderTrait;
use Claroline\CoreBundle\Security\PermissionCheckerTrait;

// constructor params
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\SerializerProvider;

// Exceptions
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

// Other use
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// the bundle new entity and serializer
use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Serializer\BinderSerializer;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Binder controller
 * @category Controller
 */
class BinderController implements LoggerAwareInterface
{
    use LoggableTrait;

    use PermissionCheckerTrait;
    use RequestDecoderTrait;

    /**
     * [$om description]
     *
     * @var ObjectManager [desc]
     */
    private $om;

    /**
     * [$crud description]
     * @var [type]
     */
    private $crud;

    /**
     * [$serializer description]
     * @var [type]
     */
    private $serializer;


    /**
     * [__construct description]
     *
     * @param AuthorizationCheckerInterface $authorization [description]
     * @param ObjectManager                 $om            [description]
     * @param Crud                          $crud          [description]
     * @param SerializerProvider            $serializer    [description]
     */
    public function __construct(
        AuthorizationCheckerInterface $authorization,
        ObjectManager $om,
        Crud $crud,
        SerializerProvider $serializer
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
    }

    /**
     * Update the document
     *
     * @param Binder  $binder  [description]
     * @param Request $request [description]
     *
     * @return JsonResponse [<description>]
     *
     * @Route("/binder/{id}", name="sidpt_binder_update", methods={"PUT"})
     * @EXT\ParamConverter(
     *     "binder",
     *     class="SidptBinderBundle:Binder",
     *     options={"mapping": {"id": "uuid"}})
     */
    public function updateAction(Binder $binder, Request $request): JsonResponse
    {
        $this->checkPermission('EDIT', $binder->getResourceNode(), [], true);
        $data = $this->decodeRequest($request);
        $object = $this->crud->update(Binder::class, $data);
        return new JsonResponse(
            $this->serializer->serialize($object)
        );
    }

    /**
     * Load a binder with its tab
     * (used for subbinder lazy loading)
     *
     * @param Binder  $binder  [description]
     * @param Request $request [description]
     *
     * @return JsonResponse [<description>]
     *
     * @Route("/binder/{id}", name="sidpt_binder_load", methods={"GET"})
     * @EXT\ParamConverter(
     *     "binder",
     *     class="SidptBinderBundle:Binder",
     *     options={"mapping": {"id": "uuid"}})
     */
    public function binderLoad(Binder $binder, Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->serializer->serialize($binder)
        );
    }



    
}
