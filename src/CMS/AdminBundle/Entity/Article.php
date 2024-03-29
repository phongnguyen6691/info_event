<?php

namespace CMS\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use CMS\AdminBundle\Api\ImageResizeApi;
use CMS\AdminBundle\Api\ConvertToSlugApi;

/**
 * Article
 *
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="CMS\AdminBundle\Entity\ArticleRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"title"},
 *   errorPath="title",
 *   message="This title is already in use, please chose another one."
 * )
 * @UniqueEntity(
 *  fields={"url"},
 *   errorPath="url",
 *   message="This url is already in use, please chose another one."
 * )
 */
class Article
{
    const ACTIVE_POST = 1;
    const ACTIVE_CANCEL = 0;
    const ACTIVE_DRAFT = 2;
    const ACTIVE_PENDING_APPROVE = 3;
    const ACTIVE_PENDING_POST = 4;

    const LOCKED_YES = true;
    const LOCKED_NO = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false, unique=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     * @Assert\NotNull(
     *  message = "The description content is not null."
     * )
     *
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_description", type="text", nullable=false)
     */
    private $sortDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false, unique=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_create", type="datetime", nullable=false)
     */
    private $dateCreate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_start", type="datetime", nullable=true)
     * @Assert\DateTime(
     *   message = "The date start '{{ value }}' is not a valid date time."
     * )
     */
    private $dateStart;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_group_article", type="integer", nullable=true)
     */
    private $idGroupArticle;

    /**
     * @var integer
     *
     * @ORM\Column(name="views", type="bigint", nullable=false)
     */
    private $views;
     
    /**
     * @var integer
     *
     * @ORM\Column(name="is_active", type="smallint", length=1, nullable=true)
     */
    private $isActive;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_locked", type="smallint", length=1, nullable=true)
     */
    private $isLocked;

    /**
     * @var string
     *
     * @ORM\Column(name="tags", type="string", length=500, nullable=true)
     */
    private $tags;
    
    /**
    * @ORM\ManyToOne(targetEntity="GroupArticle", inversedBy="articles")
    * @ORM\JoinColumn(name="id_group_article", referencedColumnName="id")
    */
    protected $groupArticle;

    /**
     * @ORM\ManyToMany(targetEntity="SpecialGroupArticle", inversedBy="articles")
     *
     */
    private $specialGroupArticle;

    /**
     * @Assert\File(
     *     maxSize = "6000000",
     *     mimeTypes = {"image/jpeg", "image/jpg", "image/png"},
     *     mimeTypesMessage = "Please upload a valid Avatar (.png, .jpg, .jpeg) and smaller 2 Mb"
     * )
     */
    private $file;

    private $temp;

    public function __construct()
    {
        $this->specialGroupArticle = new ArrayCollection();
        $this->isActive = self::ACTIVE_CANCEL;
        $this->isLocked = self::LOCKED_NO;
    }

    public  function  __toString(){
        return $this->title;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Article
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set tags
     *
     * @param string $tags
     * @return Article
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Article
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sortDescription
     *
     * @param string $description
     * @return Article
     */
    public function setSortDescription($sortDescription)
    {
        $this->sortDescription = $sortDescription;

        return $this;
    }

    /**
     * Get sortDescription
     *
     * @return string
     */
    public function getSortDescription()
    {
        return $this->sortDescription;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Article
     */
    public function setUrl($url)
    {
        $slug = new ConvertToSlugApi($url);
        $this->url = $slug->convert();

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setUrlValue()
    {
        if (!$this->getUrl())
        {
            $slug = new ConvertToSlugApi($this);
            $this->url = $slug->convert().'.html';
        }
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return Article
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set dateCreate
     *
     * @param \DateTime $dateCreate
     * @return Article
     */
    public function setDateCreate($dateCreate)
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    /**
     * Get dateCreate
     *
     * @return \DateTime
     */
    public function getDateCreate()
    {
        return $this->dateCreate;
    }

    /**
     * Set DateStart
     *
     * @param \DateTime $dateStart
     * @return Article
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Get DateStart
     *
     * @return \DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set idGroupArticle
     *
     * @param integer $idGroupArticle
     * @return Article
     */
    public function setIdGroupArticle($idGroupArticle)
    {
        $this->idGroupArticle = $idGroupArticle;

        return $this;
    }

    /**
     * Get idGroupArticle
     *
     * @return integer
     */
    public function getIdGroupArticle()
    {
        return $this->idGroupArticle;
    }

    /**
     * Set views
     *
     * @param integer $views
     * @return Article
     */
    public function setViews($views)
    {
        $this->views = $views;

        return $this;
    }

    /**
     * Get views
     *
     * @return integer
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Set isActive
     *
     * @param string $isActive
     * @return Article
     */
    public function setIsActive($isActive)
    {
        if (!in_array($isActive, array(self::ACTIVE_CANCEL, self::ACTIVE_DRAFT, self::ACTIVE_PENDING_APPROVE, self::ACTIVE_PENDING_POST, self::ACTIVE_POST))) {
            throw new \InvalidArgumentException("Invalid active");
        }
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return string
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Get isActiveType
     *
     * @return array
     */
    public static function getIsActiveTypes()
    {
        return array(
            self::ACTIVE_CANCEL => 'Cancel',
            self::ACTIVE_DRAFT => 'Save draft',
            self::ACTIVE_PENDING_APPROVE => 'Pending approve',
            self::ACTIVE_PENDING_POST => 'Pending post',
            self::ACTIVE_POST => 'Post',
        );
    }

    /**
     * Set isLocked
     *
     * @param string $isLocked
     * @return Article
     */
    public function setIsLocked($isLocked)
    {
        if (!in_array($isLocked, array(self::LOCKED_YES, self::LOCKED_NO))) {
            throw new \InvalidArgumentException("Invalid Locked");
        }
        $this->isLocked = $isLocked;

        return $this;
    }

    /**
     * Get isLocked
     *
     * @return string
     */
    public function getIsLocked()
    {
        if($this->isLocked && $this->isLocked == 1){
            $this->isLocked = self::LOCKED_YES;
        }else
            $this->isLocked = self::LOCKED_NO;

        return $this->isLocked;
    }

    /**
     * Get isLockedTypes
     *
     * @return array
     */
    public static function getIsLockedTypes()
    {
        return array(
            self::LOCKED_YES => 'Yes',
            self::LOCKED_NO => 'No'
        );
    }


    /**
     * Set groupArticle
     *
     * @param \CMS\AdminBundle\Entity\GroupArticle $groupArticle
     * @return Article
     */
    public function setGroupArticle(\CMS\AdminBundle\Entity\GroupArticle $groupArticle = null)
    {
        $this->groupArticle = $groupArticle;

        return $this;
    }

    /**
     * Get groupArticle
     *
     * @return \CMS\AdminBundle\Entity\GroupArticle
     */
    public function getGroupArticle()
    {
        return $this->groupArticle;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDateCreatedValue()
    {
        if (!$this->getDateCreate())
        {
            $this->dateCreate = new \DateTime();
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function setViewValue()
    {
        if (!$this->getViews())
        {
            $this->views = 0;
        }
    }

    public function getAbsolutePath()
    {
        return null === $this->image
            ? null
            : $this->getUploadRootDir().'/'.$this->image;
    }

    public function getAbsoluteThumbPath()
    {
        return null === $this->image
            ? null
            : $this->getUploadRootDir().'/145x145/'.$this->image;
    }

    public function getWebPath()
    {
        return null === $this->image
            ? null
            : $this->getUploadDir().'/'.$this->image;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/article/';
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
        // check if we have an old image path
        if (isset($this->image)) {
            // store the old name to delete after the update
            $this->temp = $this->image;
            $this->image = null;
        } else {
            $this->image = 'initial';
        }
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

   /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->image = $filename.'.'.$this->getFile()->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->getFile()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getFile()->move($this->getUploadRootDir(), $this->image);

        // resize images to thumbnail
        $imagePath = $this->getUploadRootDir();
        $thumbPath = $this->getUploadRootDir().'/145x145';
        $fs = new Filesystem();
        if(!$fs->exists($thumbPath)){
            try {
                $fs->mkdir($thumbPath);
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while creating your directory at ".$e->getPath();
            }
        }
        $thumb = new ImageResizeApi($imagePath, $thumbPath, $this->image, 's_'.$this->image, 145, 1, 100);
        $thumb->resize();

        // resize images to small image
        $smallPath = $this->getUploadRootDir().'/300x300';
        $fs = new Filesystem();
        if(!$fs->exists($smallPath)){
            try {
                $fs->mkdir($smallPath);
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while creating your directory at ".$e->getPath();
            }
        }
        $small = new ImageResizeApi($imagePath, $smallPath, $this->image, 'm_'.$this->image, 300, 0, 100);
        $small->resize();

        // check if we have an old image
        if (isset($this->temp)) {
            // delete the old image
            if(file_exists($this->getUploadRootDir().'/'.$this->temp))
                unlink($this->getUploadRootDir().'/'.$this->temp);

            if(file_exists($this->getUploadRootDir().'/145x145/s_'.$this->temp))
                unlink($this->getUploadRootDir().'/145x145/s_'.$this->temp);

            if(file_exists($this->getUploadRootDir().'/300x300/m_'.$this->temp))
                unlink($this->getUploadRootDir().'/300x300/m_'.$this->temp);

            // clear the temp image path
            $this->temp = null;
        }
        $this->file = null;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            if(file_exists($file))
            unlink($file);
        }

        if ($thumb = $this->getAbsoluteThumbPath()) {
            if(file_exists($thumb))
                unlink($thumb);
        }
    }

    /**
     * Add specialGroupArticle
     *
     * @param \CMS\AdminBundle\Entity\SpecialGroupArticle $tags
     * @return Article
     */
    public function addSpecialGroupArticle(\CMS\AdminBundle\Entity\SpecialGroupArticle $specialGroupArticle)
    {
        $this->specialGroupArticle[] = $specialGroupArticle;

        return $this;
    }

    /**
     * Remove specialGroupArticle
     *
     * @param \CMS\AdminBundle\Entity\SpecialGroupArticle $tags
     */
    public function removeSpecialGroupArticle(\CMS\AdminBundle\Entity\SpecialGroupArticle $specialGroupArticle)
    {
        $this->specialGroupArticle->removeElement($specialGroupArticle);
    }

    /**
     * Get specialGroupArticle
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSpecialGroupArticle()
    {
        return $this->specialGroupArticle;
    }
}