<?php

namespace Sidpt\BinderBundle\API\Manager;


use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\DataSource;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Entity\Widget\Type\SimpleWidget;
use Claroline\CoreBundle\Entity\Widget\Type\ListWidget;
use Claroline\CoreBundle\Entity\Widget\Type\ResourceWidget;
use Claroline\CoreBundle\Entity\Widget\Widget;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Widget\WidgetContainerConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;

// entities
use Claroline\CoreBundle\Entity\Widget\WidgetInstanceConfig;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Manager\ContentTranslationManager;
use Claroline\CoreBundle\Manager\Organization\OrganizationManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\HomeBundle\Entity\HomeTab;
use Claroline\TagBundle\Manager\TagManager;
use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\Document;

use UJM\ExoBundle\Library\Options\ExerciseType;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;


class DocumentManager implements LoggerAwareInterface
{
  use LoggableTrait;

  // Class parameters
  private $om;
  private $crud;
  private $serializer;
  private $finder;
  private $organizationManager;
  private $tagManager;
  private $roleManager;
  private $workspaceManager;
  private $resourceManager;

  // Class variables for execute function (hoping it can be help performances)
  private $workspaceRepo;
  private $resourceNodeRepo;
  private $homeTabsRepo;
  private $resourceWidgetsRepo;
  private $listWidgetsRepo;

  private $binderType;
  private $documentType;
  private $directoryType;
  private $lessonType;
  private $exerciseType;
  private $textType;
  private $resourceDataSource;
  private $resourcesListDataSource;
  private $simpleWidgetType;
  private $resourceWidgetType;
  private $listWidgetType;

  private $nodeSeralizer;

  // Tags hierarchy
  private $contentLevel;
  private $professionnalProfile;
  private $estimatedTime;
  private $includedResources;

  private $translations;

  const BANNER_IMAGES_LINK = [
    'Activity' => 'bundles/sidptbinder/images/lu/Activity.jpeg', // Activity banner image
    'References' => 'bundles/sidptbinder/images/lu/References.jpeg',  // References banner image
    'Theory' => 'bundles/sidptbinder/images/lu/Theory.jpeg', // Theory banner image
  ];


  /**
   *
   */
  public function __construct(
      ObjectManager $om,
      Crud $crud,
      SerializerProvider $serializer,
      FinderProvider $finder,
      OrganizationManager $organizationManager,
      TagManager $tagManager,
      RoleManager $roleManager,
      WorkspaceManager $workspaceManager,
      ResourceManager $resourceManager,
      ContentTranslationManager $translations
  ) {
      $this->translations = $translations;
      $this->om = $om;
      $this->crud = $crud;
      $this->serializer = $serializer;
      $this->finder = $finder;
      $this->organizationManager = $organizationManager;
      $this->tagManager = $tagManager;
      $this->roleManager = $roleManager;
      $this->workspaceManager = $workspaceManager;
      $this->resourceManager = $resourceManager;

      $this->workspaceRepo = $this->om->getRepository(Workspace::class);
      $this->resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
      $this->homeTabsRepo = $this->om->getRepository(HomeTab::class);
      $this->resourceWidgetsRepo = $this->om->getRepository(ResourceWidget::class);
      $this->listWidgetsRepo = $this->om->getRepository(ListWidget::class);

      $widgetsTypeRepo = $this->om->getRepository(Widget::class);
      $dataSourceRepo = $this->om->getRepository(DataSource::class);
      $typesRepo = $this->om->getRepository(ResourceType::class);

      $this->binderType = $typesRepo->findOneBy(
          ['name' => 'sidpt_binder']
      );
      $this->documentType = $typesRepo->findOneBy(
          ['name' => 'sidpt_document']
      );

      $this->directoryType = $typesRepo->findOneBy(
          ['name' => 'directory']
      );

      $this->lessonType = $typesRepo->findOneBy(
          ['name' => 'icap_lesson']
      );

      $this->exerciseType = $typesRepo->findOneBy(
          ['name' => 'ujm_exercise']
      );

      $this->textType = $typesRepo->findOneBy(
          ['name' => 'text']
      );

      $this->resourceDataSource = $dataSourceRepo->findOneBy(
          ['name' => 'resource']
      );
      $this->resourcesListDataSource = $dataSourceRepo->findOneBy(
          ['name' => 'resources']
      );

      $this->simpleWidgetType = $widgetsTypeRepo->findOneBy(
          ['name' => 'simple']
      );

      $this->resourceWidgetType = $widgetsTypeRepo->findOneBy(
          ['name' => 'resource']
      );
      $this->listWidgetType = $widgetsTypeRepo->findOneBy(
          ['name' => 'list']
      );
      $this->nodeSeralizer = $this->serializer->get(ResourceNode::class);

  }



  public function configureAsLearningUnit(
    Document $learningUnitDocument,
    bool $flush = true
  ){
    $learningUnitNode = $learningUnitDocument->getResourceNode();
    $learningUnitDocument->setShowOverview(true);
    $learningUnitDocument->setWidgetsPagination(true);
    $learningUnitDocument->setShowDescription(true);

    $learningUnitDocument->setDisclaimer(null);
    $learningUnitDocument->setOverviewMessage(null);
    $learningUnitDocument->setDescriptionTitle(null);

    $description = $learningUnitNode->getDescription();
    $learningOutcomeContent = $description;
     // update previous description
     if (!empty($description)) {
         // original template
         $searchedOutcome = explode(
             "<h3>Learning outcomes</h3>",
             $description
         );
         if (count($searchedOutcome) > 1) {
             $docIsLU = true;
             $learningOutcomeContent = explode(
                 "<p>{{#resource.resourceNode.tags[\"Disclaimer\"] }}</p>",
                 $searchedOutcome[1]
             )[0];
             $learningOutcomeContent = trim($learningOutcomeContent);
             $learningOutcomeContent = substr(
                 $learningOutcomeContent,
                 0,
                 strlen($learningOutcomeContent)
             );
         } else {
           $searchedOutcome = explode(
               "<h3>Learning outcome</h3>",
               $description
           );
           if (count($searchedOutcome) > 1) {
               $docIsLU = true;
               $learningOutcomeContent = explode(
                   "<p>{{#resource.resourceNode.tags[\"Disclaimer\"] }}</p>",
                   $searchedOutcome[1]
               )[0];
               $learningOutcomeContent = trim($learningOutcomeContent);
               $learningOutcomeContent = substr(
                   $learningOutcomeContent,
                   0,
                   strlen($learningOutcomeContent)
               );
           } else {
               // template v2
               // (translations)
               $searchedOutcome = explode(
                   "<h3>{trans('Learning outcomes','clarodoc')}</h3>",
                   $description
               );
               if (count($searchedOutcome) > 1) {
                   $docIsLU = true;
                   $learningOutcomeContent = explode(
                       "<p id=\"disclaimer-start\">",
                       $searchedOutcome[1]
                   )[0];
                   $learningOutcomeContent = trim($learningOutcomeContent);
               }
           }
        }
     }

    $learningUnitNode->setDescription($learningOutcomeContent);

    $user = $learningUnitNode->getCreator();
    $requiredKnowledgeNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Required knowledge",
        $this->directoryType,
        false
    );

    $learningUnitDocument->setRequiredResourceNodeTreeRoot($requiredKnowledgeNode);

    // - a practice exercise:
    $practiceNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Practice",
        $this->exerciseType
    );
    // - A theroy lesson
    $theoryNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Theory",
        $this->lessonType
    );

    // - An assessment exercice
    $assessmentNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Assessment",
        $this->exerciseType
    );


    // - An activity text
    $activityNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Activity",
        $this->textType
    );

    // - A references document
    $referencesNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "References",
        $this->documentType
    );
    $referencesDocument = $this->resourceManager->getResourceFromNode($referencesNode);
    $this->om->flush();
    // The reference document contains 2 sections :
    // - an external reference textual section
    $externalReferencesNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $referencesNode,
        "External references",
        $this->textType
    );
    // - an internal reference folder, to store a hierarchy of shortcuts
    $internalReferencesNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $referencesNode,
        "IPIP references",
        $this->directoryType
    );
    // */

    $this->om->persist($learningUnitNode);
    $this->om->persist($learningUnitDocument);
    if($flush){
      $this->om->flush();
    }

    // TODO build tag hierarchy
    // Get or create the the module tag (plateforme tags)
    // $learningUnitTag = $this->tagManager->getOnePlatformTagByName($learningUnitNode->getName(), $moduleTag);
    // if (empty($learningUnitTag)) {
    //     $learningUnitTag = new Tag();
    //     $learningUnitTag->setName($learningUnit);
    //     $learningUnitTag->setParent($moduleTag);
    //     $this->om->persist($learningUnitTag);
    // }
    // $this->tagManager->tagData(
    //     ['Content level/Learning unit', $learningUnitTag->getPath()],
    //     [ 0 => [
    //         'id'=> $learningUnitNode->getUuid(),
    //         'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
    //         'name' => "{$curriculum}/{$course}/{$module}/${learningUnit}"
    //     ]]
    // );
    // $this->om->flush();

  }

  public function configureAsModule(
    Document $moduleDocument,
    bool $flush = true
  ){
    // set description
    // set learning unit resource list
    $moduleNode = $moduleDocument->getResourceNode();
    $moduleDocument->setShowOverview(false);
    $moduleDocument->setWidgetsPagination(false);
    $this->addOrUpdateResourceListWidget($moduleDocument,$moduleNode, "Learning units");

    $this->om->persist($moduleNode);
    $this->om->persist($moduleDocument);
    if($flush){
      $this->om->flush();
    }

  }

  public function configureAsCourse(
    Document $courseDocument,
    bool $flush = true
  ){
    //  set description
    // set modules resources list
    $courseNode = $courseDocument->getResourceNode();
    $courseDocument->setShowOverview(false);
    $courseDocument->setWidgetsPagination(false);

    $this->addOrUpdateResourceListWidget($courseDocument,$courseNode, "Modules");

    $courseNode->setDescription(<<<HTML
    <p style="margin-bottom:0px;">{trans('Modules','clarodoc')}: </p>
    <ul>{{#resource.resourceNode.children}}
    <li><a id="{{ slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{workspace.slug}}/resources/{{slug}}">{{ name }}</a></li>
    {{/resource.resourceNode.children}}</ul>
    HTML);

    $this->om->persist($courseNode);
    $this->om->persist($courseDocument);
    if($flush){
      $this->om->flush();
    }
  }


  /**
   * [addOrUpdateDocumentSubObject description]
   * @param [type]  $user          [description]
   * @param [type]  $documentNode  [description]
   * @param [type]  $subnodeName   [description]
   * @param [type]  $resourceType  [description]
   * @param boolean $withWidget    [description]
   */
  public function addOrUpdateDocumentSubObject(
      $user,
      $documentNode,
      $subnodeName,
      $resourceType,
      $withWidget = true
  ) {
      $document = $this->resourceManager->getResourceFromNode($documentNode);
      $subNode = $this->resourceNodeRepo->findOneBy(
          [
              'name' => $subnodeName,
              'parent' => $documentNode->getId(),
              'resourceType' => $resourceType->getId(),
          ]
      );
      if (empty($subNode)) {
          $subNode = new ResourceNode();
          $subNode->setName($subnodeName);
          $subNode->setWorkspace($documentNode->getWorkspace());
          $subNode->setResourceType($resourceType);
          $subNode->setParent($documentNode);
          $subNode->setCreator($user);
          $subNode->setMimeType("custom/" . $resourceType->getName());
          $this->om->persist($subNode);

          $resourceclass = $resourceType->getClass();
          $subResource = new $resourceclass();
          $subResource->setResourceNode($subNode);
          $subResource->setName($subnodeName);

          if ($resourceType->getName() == "ujm_exercise") {
              $subResource->setShowOverview(false);
              $subResource->setShowEndConfirm(false);
              if ($subnodeName == "Practice") {
                  $subResource->setType(ExerciseType::CONCEPTUALIZATION);
                  $subResource->setScoreRule(json_encode(["type" => "none"]));
              } else if ($subnodeName == "Assessment") {
                  $subResource->setType(ExerciseType::SUMMATIVE);
                  $subResource->setScoreRule(json_encode(["type" => "sum"]));
              }
          } elseif ($resourceType->getName() == "icap_lesson") {
            // set poster in overview message
            $link = self::BANNER_IMAGES_LINK[0];
            // set poster in overview message
            $subResource->setDescription(<<<HTML
              <img src="$link" class="img-responsive" style="max-height:300px;"/>
            HTML);
            $subResource->searchIsAllowed(false);
          }

          $this->om->persist($subResource);
          $this->om->persist($document);

          if ($withWidget) {
              $this->addResourceWidget($document, $subNode, $subnodeName);
          }
      } else {
          // Update the document or node
          // Update the underlying resource
          $subResource = $this->resourceManager->getResourceFromNode($subNode);

          if ($subNode->getResourceType()->getName() == "ujm_exercise") {
              $subResource->setShowOverview(false);
              $subResource->setShowEndConfirm(false);
              if ($subResource->getType() == ExerciseType::CONCEPTUALIZATION) {
                  if (empty($subResource->getScoreRule())) {
                      $subResource->setScoreRule(
                          json_encode(["type" => "none"])
                      );
                  }
              } else if ($subResource->getType() == ExerciseType::SUMMATIVE) {
                  if (empty($subResource->getScoreRule())) {
                      $subResource->setScoreRule(
                          json_encode(["type" => "sum"])
                      );
                  }
              }
          } elseif ($resourceType->getName() == "icap_lesson") {
            $link = self::BANNER_IMAGES_LINK["Theory"];
            // set poster in overview message
            $subResource->setDescription(<<<HTML
              <img src="$link" class="img-responsive" style="max-height:300px;"/>
            HTML);
            $subResource->searchIsAllowed(false);
          }
          // update the widget
          $subNodeWidgets = $this->resourceWidgetsRepo->findBy(
              [
                  'resourceNode' => $subNode->getId(),
              ]
          );
          if (!empty($subNodeWidgets)) {
              // widget was found
              foreach ($subNodeWidgets as $widget) {
                  $widget->setShowResourceHeader(false);
                  $instance = $widget->getWidgetInstance();
                  $container = $instance->getContainer();
                  if (!$withWidget) {
                      // widget is no more requested
                      // remove the widget container
                      $this->om->remove($container);
                      $this->om->remove($instance);
                      $this->om->remove($widget);
                  } else {
                      $containerConfig = $container->getWidgetContainerConfigs()->first();
                      $containerConfig->setName($subnodeName);
                      $bannerType = $subnodeName !== "Theory" && array_key_exists($subnodeName,self::BANNER_IMAGES_LINK);
                      // Update container for widget section that should have a banner
                      if($bannerType !== false) {
                        $containerConfig->setLayout([1,2]);
                        $allInstances = $container->getInstances();
                        if($allInstances->count() == 1){
                          // poster widget is missing
                          $link = self::BANNER_IMAGES_LINK[$subnodeName];
                          // make the banner widget
                          $bannerWidget = new SimpleWidget();
                          $bannerWidget->setContent(<<<HTML
                            <img src="$link" class="img-responsive" style="max-height:300px;"/>
                          HTML);
                          $this->om->persist($bannerWidget);

                          $bannerWidgetInstance = new WidgetInstance();
                          $bannerWidgetInstance->setWidget($this->simpleWidgetType);
                          $bannerWidgetInstance->setDataSource(null);
                          $this->om->persist($bannerWidgetInstance);

                          $bannerWidget->setWidgetInstance($bannerWidgetInstance);
                          $this->om->persist($bannerWidget);

                          $bannerWidgetInstanceConfig = new WidgetInstanceConfig();
                          $bannerWidgetInstanceConfig->setType("simple");
                          $bannerWidgetInstanceConfig->setWidgetInstance($bannerWidgetInstance);
                          $bannerWidgetInstanceConfig->setPosition(0);
                          $this->om->persist($bannerWidgetInstanceConfig);

                          $instanceConfig = $instance->getWidgetInstanceConfigs()[0];
                          $instanceConfig->setPosition(1);
                          $this->om->persist($instanceConfig);

                          $container->addInstance($bannerWidgetInstance);
                          $this->om->persist($container);
                          $this->om->persist($containerConfig);

                        }
                      }
                      if($subnodeName == "IPIP references") {
                        $this->om->remove($widget);
                        // replace directory widget by resource list widget
                        $widget = new ListWidget();
                        $widget->setFilters(
                            [0 => [
                                "property" => "parent",
                                "value" => [
                                    "id" => $subNode->getUuid(),
                                    "name" => $subNode->getName(),
                                ],
                                "locked" => true,
                            ],
                            1 => [
                                "property" => "published",
                                "value" => true,
                                "locked" => true,
                            ]
                          ],
                        );

                        $widget->setDisplay("table");
                        $widget->setActions(false);
                        $widget->setCount(true);
                        $widget->setDisplayedColumns(["name"]);

                        $instance->setWidget($this->listWidgetType);
                        $instance->setDataSource($this->resourcesListDataSource);
                        $widget->setWidgetInstance($instance);

                        $widgetInstanceConfig = $instance->getWidgetInstanceConfigs()[0];
                        $widgetInstanceConfig->setType("list");

                        $this->om->persist($widgetInstanceConfig);
                        $this->om->persist($instance);

                      }

                      $this->om->persist($widget);
                  }
              }
          } elseif ($withWidget) {
              // FIXME handle post update IPIP references recreation
              // subnode is alledgedly used in a widget but was not found
              // So we add it
              $this->addResourceWidget($document, $subNode, $subnodeName);
          }
      }
      //$this->om->flush();
      return $subNode;
  }

  /**
   * [addResourceWidget description]
   * @param [type] $document     [description]
   * @param [type] $resourceNode [description]
   */
  public function addOrUpdateResourceListWidget(
    Document $document,
    ResourceNode $parentNode,
    $name = null,
    $showName = false
  ) {
      if ($document->getWidgetContainers()->isEmpty()) {
          $newWidget = new ListWidget();
          $newWidget->setFilters(
              [0 => [
                  "property" => "parent",
                  "value" => [
                      "id" => $parentNode->getUuid(),
                      "name" => $parentNode->getName(),
                  ],
                  "locked" => true,
              ],
              1 => [
                  "property" => "published",
                  "value" => true,
                  "locked" => true,
              ]
            ],
          );

          $newWidget->setDisplay("table");
          $newWidget->setActions(false);
          $newWidget->setCount(true);
          $newWidget->setDisplayedColumns(["name", "meta.description"]);
          if ($name === "Learning units") {
              // update widget to display two columns
              // - the name column should be labeled "Select a learning unit" with a
              // - the meta.description should be title "Learning outcomes"
              $newWidget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a learning unit",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "label" => "Learning outcomes",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                  ]
              );
          } elseif ($name === "Modules") {
              // update widget to display two columns
              // - the name column should be labeled "Select a module" with a
              // - the meta.description title should be removed
              $newWidget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a module",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "hideLabel" => true,
                      ],
                  ]
              );
          } else {
              // possibly course list, but might be done manually
          }

          $newWidgetInstance = new WidgetInstance();
          $newWidgetInstance->setWidget($this->listWidgetType);
          $newWidgetInstance->setDataSource($this->resourcesListDataSource);
          $newWidget->setWidgetInstance($newWidgetInstance);
          $newWidgetInstanceConfig = new WidgetInstanceConfig();
          $newWidgetInstanceConfig->setType("list");
          $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
          $newWidgetContainer = new WidgetContainer();
          $newWidgetContainer->addInstance($newWidgetInstance);
          $newWidgetInstance->setContainer($newWidgetContainer);
          $newWidgetContainerConfig = new WidgetContainerConfig();
          if($showName) {
            $newWidgetContainerConfig->setName($name);
          }
          $newWidgetContainerConfig->setBackgroundType("color");
          $newWidgetContainerConfig->setBackground("#ffffff");
          $newWidgetContainerConfig->setPosition(0);
          $newWidgetContainerConfig->setLayout(array(1));
          $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);
          $this->om->persist($newWidget);
          $this->om->persist($newWidgetInstance);
          $this->om->persist($newWidgetContainer);

          $document->addWidgetContainer($newWidgetContainer);
      } else {
          $container = $document->getWidgetContainers()->first();
          $containerConfig = $container->getWidgetContainerConfigs()->first();
          $instance = $container->getInstances()->first();

          $widget = $this->listWidgetsRepo->findOneBy(
              [
                  "widgetInstance" => $instance->getId(),
              ]
          );

          $widget->setFilters(
              [ 0 => [
                    "property" => "parent",
                    "value" => [
                        "id" => $parentNode->getUuid(),
                        "name" => $parentNode->getName(),
                    ],
                    "locked" => true,
                ],
                1 => [
                    "property" => "published",
                    "value" => true,
                    "locked" => true,
                ]
              ]
          );
          $widget->setDisplay("table");
          $widget->setActions(false);
          $widget->setCount(true);
          $widget->setDisplayedColumns(["name", "meta.description"]);

          if($showName) {
            $containerConfig->setName($name);
          } else {
            $containerConfig->setName(null);
          }


          if ($name == "Learning units") {
              // update widget to display two columns
              // - the name column should be labeled "Select a learning unit" with a
              // - the meta.description should be title "Learning outcomes"
              $widget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a learning unit",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "label" => "Learning outcomes",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                  ]
              );
          } elseif ($name == "Modules") {
              // update widget to display two columns
              // - the name column should be labeled "Select a module" with a
              // - the meta.description title should be removed
              $widget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a module",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "hideLabel" => true,
                      ],
                  ]
              );
          } else {
              // possibly course list, but might be done manually
          }

          $this->om->persist($widget);
          $this->om->persist($containerConfig);
      }
      $this->om->persist($document);
      //$this->om->flush();
  }



  /**
   * [addResourceWidget description]
   * @param [type] $document     [description]
   * @param [type] $resourceNode [description]
   */
  public function addResourceWidget($document, $resourceNode, $name = null)
  {

      $bannerType = $name !== "Theory" && array_key_exists($name,self::BANNER_IMAGES_LINK);

      if($name === "IPIP references"){
        $newWidget = new ListWidget();
        $newWidget->setFilters(
            [0 => [
                "property" => "parent",
                "value" => [
                    "id" => $resourceNode->getUuid(),
                    "name" => $resourceNode->getName(),
                ],
                "locked" => true,
            ],
            1 => [
                "property" => "published",
                "value" => true,
                "locked" => true,
            ]
          ],
        );

        $newWidget->setDisplay("table");
        $newWidget->setActions(false);
        $newWidget->setCount(true);
        $newWidget->setDisplayedColumns(["name"]);

        $newWidgetInstance = new WidgetInstance();
        $newWidgetInstance->setWidget($this->listWidgetType);
        $newWidgetInstance->setDataSource($this->resourcesListDataSource);

        $this->om->persist($newWidgetInstance);
        $newWidget->setWidgetInstance($newWidgetInstance);

        $newWidgetInstanceConfig = new WidgetInstanceConfig();
        $newWidgetInstanceConfig->setType("list");
        $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
      } else {
        $newWidget = new ResourceWidget();
        $newWidget->setResourceNode($resourceNode);
        $newWidget->setShowResourceHeader(false);
        $this->om->persist($newWidget);

        $newWidgetInstance = new WidgetInstance();
        $newWidgetInstance->setWidget($this->resourceWidgetType);
        $newWidgetInstance->setDataSource($this->resourceDataSource);
        $this->om->persist($newWidgetInstance);
        $newWidget->setWidgetInstance($newWidgetInstance);

        $newWidgetInstanceConfig = new WidgetInstanceConfig();
        $newWidgetInstanceConfig->setType("resource");
        $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
      }

      if($bannerType !== false){
        $newWidgetInstanceConfig->setPosition(1);
      }
      $this->om->persist($newWidgetInstanceConfig);

      if($bannerType !== false){
        $link = self::BANNER_IMAGES_LINK[$name];
        // make the banner widget
        $bannerWidget = new SimpleWidget();
        $bannerWidget->setContent(<<<HTML
          <img src="$link" class="img-responsive" style="max-height:300px;"/>
        HTML);
        $this->om->persist($bannerWidget);

        $bannerWidgetInstance = new WidgetInstance();
        $bannerWidgetInstance->setWidget($this->simpleWidgetType);
        $bannerWidgetInstance->setDataSource(null);
        $this->om->persist($bannerWidgetInstance);
        $bannerWidget->setWidgetInstance($bannerWidgetInstance);

        $bannerWidgetInstanceConfig = new WidgetInstanceConfig();
        $bannerWidgetInstanceConfig->setType("simple");
        $bannerWidgetInstanceConfig->setWidgetInstance($bannerWidgetInstance);
        $bannerWidgetInstanceConfig->setPosition(0);
        $this->om->persist($bannerWidgetInstanceConfig);

      }

      $newWidgetContainer = new WidgetContainer();
      if($bannerType !== false){
        $newWidgetContainer->addInstance($bannerWidgetInstance);
        $bannerWidgetInstance->setContainer($newWidgetContainer);
      }
      $newWidgetContainer->addInstance($newWidgetInstance);
      $newWidgetInstance->setContainer($newWidgetContainer);
      $this->om->persist($newWidgetContainer);

      $newWidgetContainerConfig = new WidgetContainerConfig();
      $newWidgetContainerConfig->setName($name);
      $newWidgetContainerConfig->setBackgroundType("color");
      $newWidgetContainerConfig->setBackground("#ffffff");
      $newWidgetContainerConfig->setPosition(0);
      if($bannerType !== false){
        $newWidgetContainerConfig->setLayout([1,2]);
      } else {
        $newWidgetContainerConfig->setLayout(array(1));
      }

      $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);

      $this->om->persist($newWidgetContainerConfig);

      $document->addWidgetContainer($newWidgetContainer);

      $this->om->persist($document);
  }


}
