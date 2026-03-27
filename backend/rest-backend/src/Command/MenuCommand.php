<?php

namespace App\Command;

use App\Entity\Usuarios;
use App\Entity\Productos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:menu',
    description: 'Menu Principal de la Aplicacion',
)]
class MenuCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        while (true) {
            $io->title('TE DAMOS LA BIENVENIDA A LA APP_REST');
            $io->writeln('[1] Crear Cuenta');
            $io->writeln('[2] Iniciar Sesion');
            $io->writeln('[3] Salir');
            $io->writeln('');

            $opcion = $io->ask('Seleciona una Opcion');

            if ($opcion === '1') {
                $this->registrar($io);
            } elseif ($opcion === '2') {
                $this->iniciarSesion($io);
            } else {
                $io->success('Gracias por Usar App_Rest, Hasta Luego!');
                return Command::SUCCESS;
            }
        }
    }

    private function registrar(SymfonyStyle $io): void
    {
        $io->section('Crear Cuenta');

        $nombre   = $io->ask('Nombre');
        $apellido = $io->ask('Apellido');
        $correo   = $io->ask('Correo');

        $existe = $this->em->getRepository(Usuarios::class)->findOneBy(['correo' => $correo]);
        if ($existe) {
            $io->error('Ese correo ya esta registrado!');
            return;
        }

        $password = $io->askHidden('Password');
        $edad     = (int) $io->ask('Edad');

        $usuario = new Usuarios();
        $usuario->setNombre($nombre);
        $usuario->setApellido($apellido);
        $usuario->setCorreo($correo);
        $usuario->setPassword(md5($password));
        $usuario->setEdad($edad);
        $usuario->setEstado(true);

        $this->em->persist($usuario);
        $this->em->flush();

        $io->success('Cuenta Creada Exitosamente!');
    }

    private function iniciarSesion(SymfonyStyle $io): void
    {
        $io->section('Iniciar Sesion');

        $correo   = $io->ask('Correo');
        $password = $io->askHidden('Password');

        $usuario = $this->em->getRepository(Usuarios::class)->findOneBy([
            'correo'   => $correo,
            'password' => md5($password),
        ]);

        if (!$usuario) {
            $io->error('Correo o Password Incorrecto!');
            return;
        }

        $io->success('Bienvenido ' . $usuario->getNombre() . ' ' . $usuario->getApellido() . '!');
        $this->menuUsuario($io);
    }

    private function menuUsuario(SymfonyStyle $io): void
    {
        while (true) {
            $io->title('MENU PRINCIPAL');
            $io->writeln('[1] Ver Productos');
            $io->writeln('[2] Salir');
            $io->writeln('');
            $opcion = $io->ask('Selecciona una Opcion');

            if ($opcion === '1') {
                $this->verProductos($io);
            } else {
                $io->success('Hasta Luego!');
                return;
            }
        }
    }

    private function verProductos(SymfonyStyle $io): void
    {
        $io->section('Productos Disponibles');

        $productos = $this->em->getRepository(Productos::class)->findAll();
        $filas = [];
        foreach ($productos as $p) {
            $filas[] = [$p->getId(), $p->getNombre(), '$' . $p->getPrecio(), $p->getDescripcion()];
        }
        $io->table(['ID', 'Nombre', 'Precio', 'Descripcion'], $filas);
    }
}