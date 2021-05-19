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
use Sidpt\BinderBundle\Entity\BinderTab;

use Sidpt\BinderBundle\API\Serializer\BinderSerializer;

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
        SerializerProvider $serializer,
        BinderSerializer $binderSerializer
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
        $this->binderSerializer = $binderSerializer;
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
     * @Route("/binder/{id}", name="sidpt_get_binder", methods={"GET"})
     * @EXT\ParamConverter(
     *     "binder",
     *     class="SidptBinderBundle:Binder",
     *     options={"mapping": {"id": "uuid"}}
     * )
     */
    public function getBinder(Binder $binder, Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->binderSerializer->serialize($binder)
        );
    }

    /**
     * Load a tab resource
     *
     * @param Binder  $binder  [description]
     * @param Request $request [description]
     *
     * @return JsonResponse [<description>]
     *
     * @Route("/binder/tab/{id}", name="sidpt_get_binder_tab_content", methods={"GET"})
     * @EXT\ParamConverter(
     *     "tab",
     *     class="SidptBinderBundle:BinderTab",
     *     options={"mapping": {"id": "uuid"}})
     */
    public function getBinderTabContent(BinderTab $tab, Request $request): JsonResponse
    {

        $response = null;
        $resourceNode = null ;
        $slug = $tab->getUuId();
        $options = [];
        
        if ($tab->getType() === BinderTab::TYPE_BINDER) {
            $resourceNode = $tab->getBinder()->getResourceNode();
            $slug = $resourceNode ?
                $resourceNode->getSlug() :
                $tab->getUuId();
            $options["slug_prefix"] = $slug;
            $response = $this->binderSerializer->serialize($tab->getBinder(), $options);
            
            // If the binder first tab contains a document, add it in the response
            $sortedTabs = $tab->getBinder()->getBinderTabs()->toArray();
            usort(
                $sortedTabs,
                function (BinderTab $a, BinderTab $b) {
                    return $a->getPosition() <=> $b->getPosition();
                }
            );
            if (count($sortedTabs) > 0 && $sortedTabs[0]->getType() == BinderTab::TYPE_DOCUMENT) {
                $response['displayedDocument'] = $this->serializer->serialize($sortedTabs[0]->getDocument());
                $resourceNode = $sortedTabs[0]->getDocument()->getResourceNode();
                $slug = $resourceNode ?
                    $resourceNode->getSlug() :
                    $sortedTabs[0]->getUuId();
                $response['displayedDocument']['slug'] = $slug;
            }
        } else if ($tab->getType() === BinderTab::TYPE_DOCUMENT) {
            $resourceNode = $tab->getDocument()->getResourceNode();
            $slug = $resourceNode ?
                $resourceNode->getSlug() :
                $tab->getUuId();
            if (isset($options["slug_prefix"])) {
                $slug = $options["slug_prefix"]."/".$slug;
            }
            $options["slug_prefix"] = $slug;
            $response = $this->serializer->serialize(
                $tab->getDocument(),
                $options
            );
            $response['slug'] = $slug;
        }
        
        return new JsonResponse($response);
    }
    
}
