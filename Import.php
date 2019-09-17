<?php

namespace Lsv\Magmi2ImportTest;

use Lsv\Datapump\Configuration;
use Lsv\Datapump\ItemHolder;
use Lsv\Datapump\Logger;
use Lsv\Datapump\Product\AbstractProduct;
use Lsv\Datapump\Product\ConfigurableProduct;
use Lsv\Datapump\Product\Data\BaseImage;
use Lsv\Datapump\Product\Data\BaseImageLabel;
use Lsv\Datapump\Product\Data\Category;
use Lsv\Datapump\Product\Data\GalleryImage;
use Lsv\Datapump\Product\Data\SmallImage;
use Lsv\Datapump\Product\Data\SmallImageLabel;
use Lsv\Datapump\Product\Data\ThumbnailImage;
use Lsv\Datapump\Product\Data\ThumbnailImageLabel;
use Lsv\Datapump\Product\SimpleProduct;
use Lsv\Datapump\Product\UpdateProduct;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magmi_DataPumpFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Monolog;

class Import
{

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        // Build configuration
        $etcDir = $this->filesystem->getDirectoryRead(DirectoryList::CONFIG)->getAbsolutePath();
        $envFile = $etcDir . 'env.php';
        $envData = (include $envFile);

        $this->configuration = new Configuration(
            __DIR__.'/../../../../../..',
            $envData['db']['connection']['default']['dbname'],
            $envData['db']['connection']['default']['host'],
            $envData['db']['connection']['default']['username'],
            $envData['db']['connection']['default']['password']
        );

        // Build logger
        $logDir = $this->filesystem->getDirectoryWrite(DirectoryList::LOG)->getAbsolutePath();
        $file = 'testimport.log';
        $stream = fopen($logDir.'/'.$file, 'ab');
        $monolog = new Monolog\Logger('default', [new Monolog\Handler\StreamHandler($stream)]);
        $logger = new Logger($monolog);

        // Setup magmi
        $magmi = Magmi_DataPumpFactory::getDataPumpInstance('productimport');

        // Build our item holder
        $itemholder = new ItemHolder($this->configuration, $logger, $magmi, $output);

        // Add products
        if ($input->getOption('speedtest')) {
            $this->addManyProducts($itemholder);
        } else {
            $itemholder->addProduct($this->simpleProductWithoutCategory());
            $itemholder->addProduct($this->configurableProduct());
            $itemholder->addProduct($this->simpleProductWithCategories());
            $itemholder->addProduct($this->simpleProductWithImages());
            $storeProducts = $this->storeTestProducts();
            foreach ($storeProducts as $product) {
                $itemholder->addProduct($product);
            }
        }

        // Lets import our products
        $itemholder->import();
    }

    /**
     * @return AbstractProduct[]
     */
    private function storeTestProducts(): array
    {
        /** @var SimpleProduct $simpleProduct */
        $simpleProduct = (new SimpleProduct())
            ->setName('Simple product')
            ->setSku('simple_store_product')
            ->setDescription('Product description')
            ->setPrice(15.99)
            ->setTaxClass('Taxable Goods')
            ->setQuantity(10);

        $translatedProduct = (new UpdateProduct())
            ->setType($simpleProduct->getType())
            ->setStore('de_storeview')
            ->setSku('simple_store_product')
            ->setName('einfaches produkt')
            ->setDescription('Produktbeschreibung');

        return [$simpleProduct, $translatedProduct];
    }

    private function simpleProductWithoutCategory(): SimpleProduct
    {
        return (new SimpleProduct())
            ->setName('Simple product')
            ->setSku('simple_no_category')
            ->setDescription('Product description')
            ->setPrice(15.99)
            ->setTaxClass('Taxable Goods')
            ->setQuantity(10);
    }

    private function configurableProduct(): ConfigurableProduct
    {
        $configProduct = new ConfigurableProduct(['color']);
        $configProduct->setName('Config');
        $configProduct->setSku('config');
        $configProduct->setDescription('Config product');
        $configProduct->setQuantity(false, true);
        $configProduct->setTaxClass('Taxable Goods');

        $configSimple1 = new SimpleProduct();
        $configSimple1->set('color', 'blue');
        $configSimple1->setName('Config simple 1');
        $configSimple1->setSku('config-simple-1');
        $configSimple1->setDescription('Config simple 1');
        $configSimple1->setPrice(10);
        $configSimple1->setTaxClass('Taxable Goods');
        $configSimple1->setQuantity(50);
        $configSimple1->setVisibility(false, false);
        $configProduct->addSimpleProduct($configSimple1);

        $configSimple2 = new SimpleProduct();
        $configSimple2->set('color', 'green');
        $configSimple2->setName('Config simple 2');
        $configSimple2->setSku('config-simple-2');
        $configSimple2->setDescription('Config simple 2');
        $configSimple2->setPrice(15.99);
        $configSimple2->setTaxClass('Taxable Goods');
        $configSimple2->setQuantity(11);
        $configSimple2->setVisibility(false, false);
        $configProduct->addSimpleProduct($configSimple2);

        return $configProduct;
    }

    private function simpleProductWithCategories(): SimpleProduct
    {
        return (new SimpleProduct())
            ->setName('Simple product - With categories')
            ->setSku('simple_with_categories')
            ->setDescription('Product description - With categories')
            ->setPrice(14.99)
            ->setTaxClass('Taxable Goods')
            ->setQuantity(10)
            ->addData(new Category('level1.0/level2.0/level3.0'))
            ->addData(new Category('level1.1/level2.1/level3.1'))
            ->addData(new Category('level1.1&level2.2', false, true, true, '&'));
    }

    private function simpleProductWithImages(): SimpleProduct
    {
        /** @var SimpleProduct $simpleProduct */
        $simpleProduct = (new SimpleProduct())
            ->setName('Simple product - With images')
            ->setSku('simple_with_images')
            ->setDescription('Product description - With images')
            ->setPrice(17.75)
            ->setTaxClass('Taxable Goods')
            ->setQuantity(20);

        // Images
        $simpleProduct->addData(new BaseImage(new File(__DIR__.'/dummy_base.png')));
        $simpleProduct->addData(new BaseImageLabel('base dummy image'));

        $simpleProduct->addData(new SmallImage(new File(__DIR__.'/dummy_small.png')));
        $simpleProduct->addData(new SmallImageLabel('small dummy image'));

        $simpleProduct->addData(new ThumbnailImage(new File(__DIR__.'/dummy_thumbnail.png')));
        $simpleProduct->addData(new ThumbnailImageLabel('thumbnail dummy image'));

        // Gallery images
        $simpleProduct->addData(new GalleryImage(new File(__DIR__.'/dummy_gallery_1.png'), 'gallery image 1'));
        $simpleProduct->addData(new GalleryImage(new File(__DIR__.'/dummy_gallery_2.png'), 'gallery image 2'));
        $simpleProduct->addData(new GalleryImage(new File(__DIR__.'/dummy_gallery_3.png'), 'gallery image 3'));

        return $simpleProduct;
    }

    private function addManyProducts(ItemHolder $holder): void
    {
        $createProduct = static function ($counter, $price, $quantity) {
            return (new SimpleProduct())
                ->setName('Product name ' . $counter)
                ->setSku('product_' . $counter)
                ->setDescription('Description ' . $counter)
                ->setPrice($price)
                ->setTaxClass('Taxable Goods')
                ->setQuantity($quantity);
        };

        for($i = 0; $i < 4000; $i++) {
            $holder->addProduct($createProduct($i, random_int(0, 5000), random_int(0, 10)));
        }
    }

}
