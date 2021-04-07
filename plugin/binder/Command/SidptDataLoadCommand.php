<?php


namespace Sidpt\BinderBundle\Command;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\SerializerProvider;

use Claroline\AppBundle\Command\BaseCommandTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Command\AdminCliCommand;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User as UserEntity;
use Claroline\CoreBundle\Security\PlatformRoles;
use Claroline\CoreBundle\Manager\Organization\OrganizationManager;
use Claroline\TagBundle\Manager\TagManager;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


// entities
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Entity\Resource\Directory;

use Claroline\TagBundle\Entity\Tag;
use Claroline\TagBundle\Entity\TaggedObject;

use Icap\LessonBundle\Entity\Lesson;
use Claroline\CoreBundle\Entity\Resource\Text;
use UJM\ExoBundle\Entity\Exercise;
use UJM\ExoBundle\Library\Options\ExerciseType;

use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\Document;

/**
 * Create the course hierarchy for the SIDPT project
 */
class SidptDataLoadCommand extends Command
{

    private $om;
    private $crud;
    private $serializer;
    private $organizationManager;
    private $tagManager;


    public function __construct(
        ObjectManager $om,
        Crud $crud,
        SerializerProvider $serializer,
        OrganizationManager $organizationManager,
        TagManager $tagManager
    ) {
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
        $this->organizationManager = $organizationManager;
        $this->tagManager = $tagManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate the courses hierarchy for the SIDPT project')
            ->addArgument('csv_path', InputArgument::REQUIRED, 'The absolute path to the csv file containing a list of curriculum/course/module/learning units to import')
            ->addArgument('username', InputArgument::OPTIONAL, 'the user login to be used as resources creator', 'claroline-connect');
    }




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $file = $input->getArgument('csv_path');
        $username = $input->getArgument('username');

        $lines = str_getcsv(file_get_contents($file), PHP_EOL);

        $workspaceRepo = $this->om->getRepository(Workspace::class);
        $resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
        $binderRepo = $this->om->getRepository(Binder::class);
        $documentRepo = $this->om->getRepository(Document::class);
        $typesRepo = $this->om->getRepository(ResourceType::class);
        $tagsRepo = $this->om->getRepository(Tag::class);
        $taggedObjectRepo = $this->om->getRepository(TaggedObject::class);

        $binderType = $typesRepo->findOneBy([
            'name' => 'sidpt_binder'
        ]);
        $documentType = $typesRepo->findOneBy([
            'name' => 'sidpt_document'
        ]);

        $directoryType = $typesRepo->findOneBy([
            'name' => 'directory'
        ]);

        $lessonType = $typesRepo->findOneBy([
            'name' => 'icap_lesson'
        ]);

        $exerciseType = $typesRepo->findOneBy([
            'name' => 'ujm_exercise'
        ]);

        $textType = $typesRepo->findOneBy([
            'name' => 'text'
        ]);

        $documentSeralizer = $this->serializer->get(Document::class);
        $nodeSerializer = $this->serializer->get(ResourceNode::class);

        $user = $this->om->getRepository(UserEntity::class)->findOneBy(['username'=>$username]);

        // To consider for other types :
        // first part of a path is a workspace, last part is a sidpt_document
        

        foreach ($lines as $key => $line) {
            $fields = str_getcsv($line, ';');
            $curriculum = trim($fields[0]);
            $course = trim($fields[1]);
            $module = trim($fields[2]);
            $learningUnit = trim($fields[3]);
            
            // Check if curriculum exist :
            // Curriculum is alledgedly a workspace
            $workspace = $workspaceRepo->findOneBy(['name' => $curriculum]);
            // Check if course exist in the curriculum
            // course is a binder resource
            if (empty($workspace)) {
                $workspaceCode = str_replace(" ", "_", strtolower($curriculum));
                $output->writeln("Making workspace {$curriculum} with code ${workspaceCode}");
                $workspace = new Workspace();
                
                $workspace->addOrganization($this->organizationManager->getDefault());
                $workspace->setCreator($user);

                $data = [
                    "name" => $curriculum,
                    "code" => $workspaceCode,
                    "meta" => [
                      "model" => false,
                      "personal" => false,
                      "usedStorage" => 0,
                      "totalUsers" => 0,
                      "totalResources" => 0,
                      "forceLang" => false
                    ],
                    "roles" => [],
                    "opening" => [
                      "type" => 'tool',
                      "target" => 'home'
                    ],
                    "display" => [
                      "showProgression" => true,
                      "showMenu" => true
                    ],
                    "breadcrumb" => [
                      "displayed" => true,
                      "items" => ['desktop', 'workspaces', 'current', 'tool']
                    ],
                    "registration" => [
                      "validation" => false,
                      "selfRegistration" => true,
                      "selfUnregistration" => true
                    ],
                    "restrictions" => [
                      "hidden" => false,
                      "dates" => []
                    ],
                    "notifications" => [
                      "enabled" => false
                    ]
                ];
                $this->serializer->deserialize($data, $workspace);

                $this->om->persist($workspace);
                $this->om->flush();

                $this->tagManager->tagData(
                    ['Curriculum',$curriculum],
                    [ 0 => [
                            'id'=> $workspace->getUuid(),
                            'class' => "Claroline\CoreBundle\Entity\Workspace\Workspace"
                    ]]
                );

            }
            $curriculumNode = $resourceNodeRepo->findOneBy([
                'name' => $curriculum,
                'workspace' => $workspace->getId(),
                'resourceType' => $directoryType->getId()
            ]);
            if (empty($curriculumNode)) {
                $output->writeln("Making node and directory {$curriculum}");
                $curriculumNode = new ResourceNode();
                $curriculumNode->setName($curriculum);
                $curriculumNode->setWorkspace($workspace);
                $curriculumNode->setResourceType($directoryType);
                $curriculumNode->setCreator($user);
                $curriculumNode->setMimeType("custom/directory");

                $curriculumDirectory = new Directory();
                $curriculumDirectory->setResourceNode($curriculumNode);
                $curriculumDirectory->setName($curriculum);

                $this->om->persist($curriculumNode);
                $this->om->persist($curriculumDirectory);
                $this->om->flush();

            }
            $curriculumSummaryNode = $resourceNodeRepo->findOneBy([
                'name' => "Summary",
                'parent' => $curriculumNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $documentType->getId(),
            ]);
            if (empty($curriculumSummaryNode)) {
                $curriculumSummaryNode = new ResourceNode();
                $curriculumSummaryNode->setName("Summary");
                $curriculumSummaryNode->setWorkspace($workspace);
                $curriculumSummaryNode->setResourceType($documentType);
                $curriculumSummaryNode->setCreator($user);
                $curriculumSummaryNode->setParent($curriculumNode);
                $curriculumSummaryNode->setMimeType("custom/sidpt_document");

                $curriculumSummary = new Document();
                $curriculumSummary->setResourceNode($curriculumSummaryNode);
                $curriculumSummary->setName("Summary");

                $this->om->persist($curriculumSummaryNode);
                $this->om->persist($curriculumSummary);
                $this->om->flush();

                // Add the summary as first tab of the workspace home
            }



            $courseNode = $resourceNodeRepo->findOneBy([
                'name' => $course,
                'parent'=> $curriculumNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $binderType->getId()
            ]);
            if (empty($courseNode)) {
                $output->writeln("Making node and binder {$curriculum}/{$course}");
                $courseNode = new ResourceNode();
                $courseNode->setName($course);
                $courseNode->setWorkspace($workspace);
                $courseNode->setResourceType($binderType);
                $courseNode->setParent($curriculumNode);
                $courseNode->setCreator($user);
                $courseNode->setMimeType("custom/sidpt_binder");

                $courseBinder = new Binder();
                $courseBinder->setResourceNode($courseNode);
                $courseBinder->setName($course);
                $this->om->persist($courseNode);
                $this->om->persist($courseBinder);
                
                $this->om->flush();

                $this->tagManager->tagData(
                    ['Course',$curriculum, $course],
                    [ 0 => [
                            'id'=> $courseNode->getUuid(),
                            'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode"
                    ]]
                );

                // Add a new tab the curriculum workspace, and add the binder to it
            }
            $courseSummaryNode = $resourceNodeRepo->findOneBy([
                'name' => "Summary",
                'parent'=> $courseNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $documentType->getId()
            ]);
            if (empty($courseSummaryNode)) {
                $courseSummaryNode = new ResourceNode();
                $courseSummaryNode->setName("Summary");
                $courseSummaryNode->setWorkspace($workspace);
                $courseSummaryNode->setResourceType($documentType);
                $courseSummaryNode->setCreator($user);
                $courseSummaryNode->setParent($courseNode);
                $courseSummaryNode->setMimeType("custom/sidpt_document");

                $courseSummary = new Document();
                $courseSummary->setResourceNode($courseSummaryNode);
                $courseSummary->setName("Summary");

                $this->om->persist($courseSummaryNode);
                $this->om->persist($courseSummary);
                $this->om->flush();

                // Add the summary as first tab of the course binder 

            }

            // Check if module exist in the course
            // module is also a binder
            
            $moduleNode = $resourceNodeRepo->findOneBy([
                'name' => $module,
                'parent'=>$courseNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $binderType->getId()
            ]);
            if (empty($moduleNode)) {
                $output->writeln("Making node and binder {$curriculum}/{$course}/{$module}");
                $moduleNode = new ResourceNode();
                $moduleNode->setName($module);
                $moduleNode->setWorkspace($workspace);
                $moduleNode->setResourceType($binderType);
                $moduleNode->setParent($courseNode);
                $moduleNode->setCreator($user);
                $moduleNode->setMimeType("custom/sidpt_binder");

                $moduleBinder = new Binder();
                $moduleBinder->setResourceNode($moduleNode);
                $moduleBinder->setName($module);

                $this->om->persist($moduleNode);
                $this->om->persist($moduleBinder);
                $this->om->flush();

                $this->tagManager->tagData(
                    ['Module', $curriculum, $course, $module],
                    [ 0 => [
                            'id'=> $moduleNode->getUuid(),
                            'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode"
                    ]]
                );
            }

            $moduleSummaryNode = $resourceNodeRepo->findOneBy([
                'name' => "Summary",
                'parent'=> $moduleNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $documentType->getId()
            ]);
            if (empty($moduleSummaryNode)) {
                $moduleSummaryNode = new ResourceNode();
                $moduleSummaryNode->setName("Summary");
                $moduleSummaryNode->setWorkspace($workspace);
                $moduleSummaryNode->setResourceType($documentType);
                $moduleSummaryNode->setCreator($user);
                $moduleSummaryNode->setParent($moduleNode);
                $moduleSummaryNode->setMimeType("custom/sidpt_document");

                $moduleSummary = new Document();
                $moduleSummary->setResourceNode($moduleSummaryNode);
                $moduleSummary->setName("Summary");

                $this->om->persist($moduleSummaryNode);
                $this->om->persist($moduleSummary);
                $this->om->flush();

                // Add the summary as first tab of the module binder

            }


            // Check if learning unit exist within the module
            // a learning unit is a document
            $learningUnitNode = $resourceNodeRepo->findOneBy([
                'name' => $learningUnit,
                'parent'=>$moduleNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $documentType->getId()
            ]);
            if (empty($learningUnitNode)) {
                $output->writeln("Making node and document {$curriculum}/{$course}/{$module}/{$learningUnit}");
                $learningUnitNode = new ResourceNode();
                $learningUnitNode->setName($learningUnit);
                $learningUnitNode->setWorkspace($workspace);
                $learningUnitNode->setResourceType($documentType);
                $learningUnitNode->setParent($moduleNode);
                $learningUnitNode->setCreator($user);
                $learningUnitNode->setMimeType("custom/sidpt_document");

                $learningUnitDocument = new Document();
                $learningUnitDocument->setResourceNode($learningUnitNode);
                $learningUnitDocument->setName($learningUnit);

                $this->om->persist($learningUnitNode);
                $this->om->persist($learningUnitDocument);
                $this->om->flush();

                $this->tagManager->tagData(
                    ['Learning unit',$curriculum, $course, $module, $learningUnit],
                    [ 0 => [
                            'id'=> $learningUnitNode->getUuid(),
                            'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode"
                    ]]
                );

                // for each learning unit, pre-creating the default resources
                // that is :
                // - a practice "exercise":
                $practiceNode = new ResourceNode();
                $practiceNode->setName("Practice");
                $practiceNode->setWorkspace($workspace);
                $practiceNode->setResourceType($exerciseType);
                $practiceNode->setParent($learningUnitNode);
                $practiceNode->setCreator($user);
                $practiceNode->setMimeType("custom/ujm_exercise");
                $practiceExercise = new Exercise();
                $practiceExercise->setResourceNode($practiceNode);
                $practiceExercise->setName("Practice");
                $practiceExercise->setType(ExerciseType::CONCEPTUALIZATION);
                $this->om->persist($practiceNode);
                $this->om->persist($practiceExercise);
                $this->om->flush();
                // - a theory "lesson":
                $theoryNode = new ResourceNode();
                $theoryNode->setName("Theory");
                $theoryNode->setWorkspace($workspace);
                $theoryNode->setResourceType($lessonType);
                $theoryNode->setParent($learningUnitNode);
                $theoryNode->setCreator($user);
                $theoryNode->setMimeType("custom/icap_lesson");
                $theoryLesson = new Lesson();
                $theoryLesson->setResourceNode($theoryNode);
                $theoryLesson->setName("Theory");
                $this->om->persist($theoryNode);
                $this->om->persist($theoryLesson);
                $this->om->flush();
                // - an assessment "Exercise":
                $assessmentNode = new ResourceNode();
                $assessmentNode->setName("Assessment");
                $assessmentNode->setWorkspace($workspace);
                $assessmentNode->setResourceType($exerciseType);
                $assessmentNode->setParent($learningUnitNode);
                $assessmentNode->setCreator($user);
                $assessmentNode->setMimeType("custom/ujm_exercise");
                $assessmentExercise = new Exercise();
                $assessmentExercise->setResourceNode($assessmentNode);
                $assessmentExercise->setName("Assessment");
                $assessmentExercise->setType(ExerciseType::SUMMATIVE);
                $this->om->persist($assessmentNode);
                $this->om->persist($assessmentExercise);
                $this->om->flush();
                // - an activity "text":
                $activityNode = new ResourceNode();
                $activityNode->setName("Activity");
                $activityNode->setWorkspace($workspace);
                $activityNode->setResourceType($textType);
                $activityNode->setParent($learningUnitNode);
                $activityNode->setCreator($user);
                $activityNode->setMimeType("custom/text");
                $activityText = new Text();
                $activityText->setResourceNode($activityNode);
                $activityText->setName("Activity");
                $this->om->persist($activityNode);
                $this->om->persist($activityText);
                $this->om->flush();
                // - A references "document"
                $referencesNode = new ResourceNode();
                $referencesNode->setName("References");
                $referencesNode->setWorkspace($workspace);
                $referencesNode->setResourceType($documentType);
                $referencesNode->setParent($learningUnitNode);
                $referencesNode->setCreator($user);
                $referencesNode->setMimeType("custom/sidpt_document");
                $referencesDocument = new Document();
                $referencesDocument->setResourceNode($referencesNode);
                $referencesDocument->setName("References");
                $this->om->persist($referencesNode);
                $this->om->persist($referencesDocument);
                $this->om->flush();
                //  This references document should hold
                //  - a widget rendering a simple page of links to external resources
                $externalReferencesNode = new ResourceNode();
                $externalReferencesNode->setName("External references");
                $externalReferencesNode->setWorkspace($workspace);
                $externalReferencesNode->setResourceType($textType);
                $externalReferencesNode->setParent($referencesNode);
                $externalReferencesNode->setCreator($user);
                $externalReferencesNode->setMimeType("custom/text");
                $externalReferencesText = new Text();
                $externalReferencesText->setResourceNode($externalReferencesNode);
                $externalReferencesText->setName("External references");
                $this->om->persist($externalReferencesNode);
                $this->om->persist($externalReferencesText);
                $this->om->flush();
                // An empty section for internal references, to be completed
                // Preparing references document
                $referencesWidgets = array(
                    0 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "External references",
                        "contents" => [
                            0 => [
                                "type" => "resource",
                                "source" => "resource",
                                "parameters" => [
                                    "showResourceHeader" => false,
                                    "resource" => $nodeSerializer->serialize($externalReferencesNode, [Options::SERIALIZE_MINIMAL])
                                ]
                            ]
                        ]
                    ],
                    1 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "Internal references",
                        "contents" => []
                    ]
                );
                $documentSeralizer->deserializeWidgets(
                    $referencesWidgets,
                    $referencesDocument
                );
                $this->om->persist($referencesDocument);
                $this->om->flush();
                
                
                // Filling the learning unit with the premade resources
                $learningUnitWidgets = array(
                    0 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "Practice",
                        "contents" => [
                            0 => [
                                "type" => "resource",
                                "source" => "resource",
                                "parameters" => [
                                    "showResourceHeader" => false,
                                    "resource" => $nodeSerializer->serialize($practiceNode, [Options::SERIALIZE_MINIMAL])
                                ]
                            ]
                        ]
                    ],
                    1 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "Theory",
                        "contents" => [
                            0 => [
                                "type" => "resource",
                                "source" => "resource",
                                "parameters" => [
                                    "showResourceHeader" => false,
                                    "resource" => $nodeSerializer->serialize($theoryNode, [Options::SERIALIZE_MINIMAL])
                                ]
                            ]
                        ]
                    ],
                    2 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "Assessment",
                        "contents" => [
                            0 => [
                                "type" => "resource",
                                "source" => "resource",
                                "parameters" => [
                                    "showResourceHeader" => false,
                                    "resource" => $nodeSerializer->serialize($assessmentNode, [Options::SERIALIZE_MINIMAL])
                                ]
                            ]
                        ]
                    ],
                    3 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "Activity",
                        "contents" => [
                            0 => [
                                "type" => "resource",
                                "source" => "resource",
                                "parameters" => [
                                    "showResourceHeader" => false,
                                    "resource" => $nodeSerializer->serialize($activityNode, [Options::SERIALIZE_MINIMAL])
                                ]
                            ]
                        ]
                    ],
                    4 => [
                        "visible" => true,
                        "display" => [
                            "layout" => [ 0 => 1 ],
                            "color" => "#333333",
                            "backgroundType" => "color",
                            "background" => "#FFFFFF"
                        ],
                        "name" => "References",
                        "contents" => [
                            0 => [
                                "type" => "resource",
                                "source" => "resource",
                                "parameters" => [
                                    "showResourceHeader" => false,
                                    "resource" => $nodeSerializer->serialize($referencesNode, [Options::SERIALIZE_MINIMAL])
                                ]
                            ]
                        ]
                    ]
                );
                $documentSeralizer->deserializeWidgets(
                    $learningUnitWidgets,
                    $learningUnitDocument
                );
                $this->om->persist($learningUnitDocument);
                $this->om->flush();
            }
        }


        return 0;
    }
}
