<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Order;
use App\Entity\Pimento;
use App\Repository\OrderRepository;
use App\Repository\PimentoRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/cart', name: 'cart_', methods: ['GET'])]
class CartController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(SessionInterface $sessionInterface, PimentoRepository $pimentoRepository): Response
    {
        $user = $this->getUser();
        $cart = $sessionInterface->get('cart', []);
        $dataCart = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $pimento = $pimentoRepository->find($id);
            $dataCart[] = [
                'pimento' => $pimento,
                'quantity' => $quantity
            ];
            $total += $pimento->getPrice() * $quantity;
        }
        return $this->render('cart/index.html.twig', ['user' => $user, 'dataCart' => $dataCart, 'total' => $total]);
    }

    #[Route('/add/{id}', name: 'add', methods: ['GET'])]
    public function add(Pimento $pimento, SessionInterface $sessionInterface): Response
    {
        $user = $this->getUser();
        $cart = $sessionInterface->get('cart', []);
        $id = $pimento->getId();
        if (empty($cart[$id])) {
            $cart[$id] = 1;
        } else {
            $cart[$id]++;
        }
        $sessionInterface->set('cart', $cart);

        return $this->redirectToRoute('cart_index', ['user' => $user]);
    }

    #[Route('/remove/{id}', name: 'remove', methods: ['GET'])]
    public function remove(Pimento $pimento, SessionInterface $sessionInterface): Response
    {
        $user = $this->getUser();
        $cart = $sessionInterface->get('cart', []);
        $id = $pimento->getId();
        if (!empty($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
        }
        $sessionInterface->set('cart', $cart);

        return $this->redirectToRoute('cart_index', ['user' => $user]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['GET'])]
    public function delete(Pimento $pimento, SessionInterface $sessionInterface): Response
    {
        $user = $this->getUser();
        $cart = $sessionInterface->get('cart', []);
        $id = $pimento->getId();
        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }
        $sessionInterface->set('cart', $cart);

        return $this->redirectToRoute('cart_index',['user' => $user]);
    }

    #[Route('/delete', name: 'delete_all', methods: ['GET'])]
    public function deleteAll(SessionInterface $sessionInterface): Response
    {
        $user = $this->getUser();
        $sessionInterface->remove("cart");

        return $this->redirectToRoute('cart_index',['user' => $user]);
    }

    #[Route('/livraison', name: 'delivery', methods: ['GET'])]
    public function delivery(): Response
    {
        $user = $this->getUser();
        return $this->render('delivery/index.html.twig',['user' => $user]);
    }

    #[Route('/paiement', name: 'payment_index', methods: ['GET'])]
    public function pay(SessionInterface $sessionInterface): Response
    {
        $user = $this->getUser();

        return $this->render('payment/index.html.twig',['user' => $user]);
    }

    #[Route('/paiement/confirmation', name: 'payment_confirmation', methods: ['GET'])]
    public function paymentConfirmation(SessionInterface $sessionInterface, PimentoRepository $pimentoRepository, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $cart = $sessionInterface->get('cart', []);
        $date = new \DateTime();
        $order = new Order;
        $order->setDate($date->format('Y-m-d H:i:s'));
        $order->setUser($user);

        foreach ($cart as $id => $quantity) {
            $pimento = $pimentoRepository->find($id);
            $dataCart[] = [
                'pimento' => $pimento,
                'quantity' => $quantity
            ];
        $order->addProduct($pimento);
        $order->setQuantity($quantity);
        $orderRepository->save($order);
        }
        $sessionInterface->remove("cart");

        return $this->redirectToRoute('home', ['user' => $user]);
    }


}
