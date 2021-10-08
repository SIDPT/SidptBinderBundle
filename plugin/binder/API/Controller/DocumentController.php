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
use Sidpt\BinderBundle\Entity\Document;
use Sidpt\BinderBundle\Serializer\DocumentSerializer;
use Sidpt\BinderBundle\API\Manager\DocumentManager;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;

/**
 *
 */
class DocumentController implements LoggerAwareInterface
{
    use LoggableTrait;

    use PermissionCheckerTrait;
    use RequestDecoderTrait;

    /**
     * [$om description]
     *
     * @var [type]
     */
    private $om;

    /**
     * [$crud description]
     *
     * @var [type]
     *
     */
    private $crud;

    /**
     * [$serializer description]
     *
     * @var [type]
     */
    private $serializer;

    private $manager;


    public function __construct(
        AuthorizationCheckerInterface $authorization,
        ObjectManager $om,
        Crud $crud,
        SerializerProvider $serializer,
        DocumentManager $manager
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
        $this->manager = $manager;
    }

    /**
     * [desc]
     *
     * @Route("/document/{id}/{templateName}", name="sidpt_document_update", methods={"PUT"})
     * @EXT\ParamConverter(
     *     "document",
     *     class="SidptBinderBundle:Document",
     *     options={"mapping": {"id": "uuid"}})
     *
     */
    public function updateAction(Document $document, Request $request, string $templateName = "default"): JsonResponse
    {
        $this->checkPermission('EDIT', $document->getResourceNode(), [], true);
        $data = $this->decodeRequest($request);
        $object = $this->crud->update(Document::class, $data);

        switch (templateName) {
          case 'learningUnit':
            $this->manager->configureAsLearningUnit($object);
            break;

          default:
            // code...
            break;
        }
        return new JsonResponse(
            $this->serializer->serialize($object)
        );
    }

    /**
     * [desc]
     *
     * @Route("/document/{id}", name="sidpt_get_document", methods={"GET"})
     * @EXT\ParamConverter(
     *     "document",
     *     class="SidptBinderBundle:Document",
     *     options={"mapping": {"id": "uuid"}})
     *
     */
    public function getDocument(Document $document, Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->serializer->serialize($document)
        );
    }



}
