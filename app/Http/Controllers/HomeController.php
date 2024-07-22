<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function index(){


        $books = Book::orderBy('created_at','DESC');

        if(!empty($request->keyword)){
            $books->where('title','like','%'.$request->keyword.'%');
        }

        $books = $books->where('status',1)->paginate(8);


        return view('home', [
            'books'=> $books
        ]);
    }

    public function detail($id){

        $book = Book::with(['reviews.user','reviews' => function($query){
            $query->where('status',1);
        }])->findOrFail($id);

        if ($book->status ==0){
            abort(404);
        }

        $relatedBooks = Book::where('status',1)->take(3)->where('id','!=', $id)->inRandomOrder()->get();

        return view('book-detail',[
            'book' => $book,
            'relatedBooks' => $relatedBooks
        ]);
    }

    public function saveReview(Request $request){
        $validator = Validator::make($request->all(),[
            'review' => 'required|min:10',
            'rating' => 'required'
        ]);

        if ($validator-> fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()
            ]);
        }
        $countReview = Review::where('user_id', Auth::user()->id)->where('book_id',$request->book_id)->count();
        if ($countReview > 0){
            session()->flash('error','You already submittted a review');
        }
        $review = new Review();
        $review->review = $request->review; // Menyimpan review teks
        $review->rating = $request->rating; // Menyimpan rating sebagai integer
        $review->user_id = Auth::user()->id; // Menyimpan ID pengguna yang saat ini login
        $review->book_id = $request->book_id; // Menyimpan ID buku
        $review->save(); // Menyimpan data ke database

        session()->flash('success', 'Review submitted successfully');

        return response()->json([
            'status' => true,
            
        ]);

    }
}
