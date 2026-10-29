<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    /**
     * Mostrar el listado de usuarios.
     */
    public function index()
    {
        $usuarios = Usuario::all();

        return view('usuario.index', compact('usuarios'));
    }

    /**
     * Mostrar el formulario para crear un usuario.
     */
    public function create()
    {
        $empresas = Empresa::all(['id_empresa', 'nombre']);

        return view('usuario.create', compact('empresas'));
    }

    /**
     * Guardar un nuevo usuario.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|max:50|unique:usuario,username',
            'password' => 'required|min:8',
            'rol' => 'required|in:admin,vendedor,supervisor',
            'activo' => 'required|boolean',
            'id_empresa' => 'nullable|exists:empresa,id_empresa',
        ]);

        Usuario::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'activo' => $request->activo,
            'fecha_creacion' => now(),
            'id_empresa' => $request->id_empresa,
        ]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Mostrar un usuario específico.
     */
    public function show(string $id)
    {
        $usuario = Usuario::findOrFail($id);

        return view('usuario.show', compact('usuario'));
    }

    /**
     * Mostrar el formulario para editar un usuario.
     */
    public function edit(Usuario $usuario)
    {
        $empresas = Empresa::all(['id_empresa', 'nombre']);

        return view('usuario.edit', compact('usuario', 'empresas'));
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(Request $request, Usuario $usuario)
    {
        $request->validate([
            'username' => 'required|max:50|unique:usuario,username,'
                . $usuario->id_usuario . ',id_usuario',
            'password' => 'nullable|min:8',
            'rol' => 'required|in:admin,vendedor,supervisor',
            'activo' => 'required|boolean',
            'id_empresa' => 'nullable|exists:empresa,id_empresa',
        ]);

        $datos = [
            'username' => $request->username,
            'rol' => $request->rol,
            'activo' => $request->activo,
            'id_empresa' => $request->id_empresa,
        ];

        // Cambiar la contraseña únicamente si se ingresó una nueva.
        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->password);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Eliminar un usuario.
     */
    public function destroy(Usuario $usuario)
    {
        // Verificar si el usuario tiene facturas asociadas.
        $tieneFacturas = DB::table('factura')
            ->where('id_usuario', $usuario->id_usuario)
            ->exists();

        if ($tieneFacturas) {
            return redirect()->route('usuarios.index')
                ->with(
                    'error',
                    'No se puede eliminar el usuario porque tiene facturas asociadas.'
                );
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
