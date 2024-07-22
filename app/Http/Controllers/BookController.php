<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;


class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::orderBy('created_at', 'DESC');

        if (!empty($request->keyword)) {
            $books->where('title', 'like', '%' . $request->keyword . '%');
        }

        $books = $books->paginate(10);

      
        $books->appends($request->all());

        return view('books.list', [
            'books' => $books,
        ]);
    }


    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
        ];

        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('books.create')->withInput()->withErrors($validator);
        }

        $book = new Book();
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        if (!empty($request->image)) {
            $image =  $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time().'.'.$ext;
            $image->move(public_path('upload/books'),$imageName);

            
            $book->image = $imageName;
            $book->save();

            $manager = new ImageManager(Driver::class); 
            $img = $manager->read(public_path('upload/books/'.$imageName));

            $img->resize(900);
            $img->save(public_path('upload/books/thumb/'.$imageName));

        }

        return redirect()->route('books.index')->with('success', 'Book added successfully');
    }

    public function edit($id)
    {
        $book = Book::findOrFail($id);
       return view('books.edit',[
        'book' => $book
       ]);
    }

    public function update($id, Request $request)
    {
        $book = Book::findOrFail($id);

        // Aturan validasi
        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
            'description' => 'nullable|min:5',
        ];
    
        // Jika ada gambar baru yang diupload, tambahkan aturan validasi gambar
        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }
    
        // Validasi data
        $validator = Validator::make($request->all(), $rules);
    
        // Jika validasi gagal, kembali ke halaman edit dengan input dan error
        if ($validator->fails()) {
            return redirect()->route('books.edit', $book->id)->withInput()->withErrors($validator);
        }
    
        // Update data buku
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        if (!empty($request->image)) {
            File::delete(public_path('upload/books/'.$book->image));
            File::delete(public_path('upload/books/thumb/'.$book->image));


            $image =  $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time().'.'.$ext;
            $image->move(public_path('upload/books'),$imageName);

            
            $book->image = $imageName;
            $book->save();

            $manager = new ImageManager(Driver::class); 
            $img = $manager->read(public_path('upload/books/'.$imageName));

            $img->resize(900);
            $img->save(public_path('upload/books/thumb/'.$imageName));
        }

        return redirect()->route('books.index')->with('success', 'Book update successfully');

    }

    public function destroy(Request $request)
    {
        $book = Book::find($request->id);

        if ( $book == null){
            session()->flash('error','Book not found');
            return response()->json([
                'status' => false,
                'message' => 'Book not found'

            ]);

        }else{
            File::delete(public_path('upload/books/' . $book->image));
            File::delete(public_path('upload/books/thumb/' . $book->image));
        

        // Hapus buku dari database
        $book->delete();


            session()->flash('success','Book delete successfully');
            return response()->json([
                'status' => true,
                'message' => 'Book delete successfully'

            ]);

        }
    }
}
