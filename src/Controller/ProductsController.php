<?php

namespace App\Controller;
use App\Entity\Products;
use App\Form\ProductsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\String\Slugger\SluggerInterface;

class ProductsController extends AbstractController
{
    // /**
    //  * @Route("/products", name="app_products")
    //  */
    // public function index(): Response
    // {
    //     return $this->render('products/index.html.twig', [
    //         'controller_name' => 'ProductsController',
    //     ]);
    // }
      /**
     * @Route("/admin/products",name="products_index")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository(Products::class)->findAll();

        return $this->render('products/index.html.twig', array(
            'products' => $products,
        ));
    }
    /**
     * @Route("/home",name="products_user")
     */
    public function indexActionUser()
    {
        
        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository(Products::class)->findAll();

        return $this->render('user_ui/home.html.twig', array(
            'products' => $products,
        ));
    }
    /**
    * Finds and displays a car entity.
    *
    * @Route("/admin/product/{id}", name="product_show")
     */
     public function showAction(Products $product)
      {
         return $this->render('product/show.html.twig', array(
        'product' => $product,
      ));
      }
  
    /**
   * Creates a new part entity.
   *
   * @Route("/admin/products/create",methods={"GET","POST"}, name="products_create")
   */
  public function createAction(Request $request, SluggerInterface  $slugger )
  {
    $product = new Products();
    $form = $this->createForm(ProductsType::class, $product);

    if ($this->saveChanges($form, $request, $product, $slugger)) {
              $this-> addFlash(
          'notice',
          'Customer Added'
      );
      return $this->redirectToRoute('products_index');
    }

    return $this->render('products/create.html.twig', [
      'form' => $form->createView()
    ]);
  }
  public function saveChanges($form, $request, $product, $slugger)
  {
      $form->handleRequest($request);
        
      if ($form->isSubmitted() && $form->isValid()) {
          $imageProduct = $form->get('imageProduct')->getData();
          
          if ($imageProduct) {
              $originalFilename = pathinfo($imageProduct->getClientOriginalName(), PATHINFO_FILENAME);
              $safeFilename = $slugger->slug($originalFilename);
              $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageProduct->guessExtension();
  
              // Move the file to the directory where products images are stored
              try {
                  $imageProduct->move(
                      $this->getParameter('app.path.product_images'),
                      $newFilename
                  );
              } catch (FileException $e) {
                  $this->addFlash('error', 'Failed to upload image.');
                  return false;
              }
  
              // set the image name in the entity
              $product->setImageProduct($newFilename);
          }
  
          $em = $this->getDoctrine()->getManager();
          $em->persist($product);
          $em->flush();
  
          return true;
      }
  
      return false;
  }
  
    
  /**
 * @Route("/admin/products/{id}/edit", name="product_edit")
 */
public function editAction($id, Request $request, SluggerInterface  $slugger )
{
    $em = $this->getDoctrine()->getManager();
    $product = $em->getRepository('App\Entity\Products')->find($id);
    
    $form = $this->createForm(ProductsType::class, $product);
    
    if ($this->saveChanges($form, $request, $product,  $slugger)) {
        $this->addFlash(
            'notice',
            'Todo Edited'
        );
        return $this->redirectToRoute('products_index');
    }
    
    return $this->render('products/edit.html.twig', [
        'form' => $form->createView()
    ]);
}


/**
 * @Route("/products/delete/{id}", name="product_delete")
 */
  public function deleteAction(int $id, EntityManagerInterface $em): Response
  {
      // Tìm sản phẩm theo ID
      $product = $em->getRepository(Products::class)->find($id);

      // Kiểm tra nếu sản phẩm không tồn tại
      if (!$product) {
          $this->addFlash('error', 'Product not found');
          return $this->redirectToRoute('products_index');
      }

      // Xóa sản phẩm
      $em->remove($product);
      $em->flush();

      // Thêm thông báo xóa thành công
      $this->addFlash('success', 'Product deleted successfully');

      // Điều hướng quay lại trang danh sách sản phẩm
      return $this->redirectToRoute('products_index');
  }

}
