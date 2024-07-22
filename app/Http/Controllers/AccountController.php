<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AccountController extends Controller
{
    public function register()
    {
        return view('account.register');
    }

    public function processRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:5',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.register')->withInput()->withErrors($validator);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('account.login')->with('success', 'You registered successfully.');
    }

    public function login()
    {
        return view('account.login');
    }

    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.login')->withInput()->withErrors($validator);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Redirect to the intended page or profile page
            return redirect()->route('account.profile');
        } else {
            return redirect()->route('account.login')->with('error', 'Either email/password is incorrect');
        }
    }

    public function profile()
    {
        $user = User::find(Auth::id());

        return view('account.profile', ['user' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . Auth::user()->id,
        ];
        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('account.profile')->withInput()->withErrors($validator);
        } 

        $user = User::find(Auth::user()->id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        if (!empty($request->image)) {
            File::delete(public_path('upload/profile/'.$user->image));
            File::delete(public_path('upload/profile/thumb/'.$user->image));

            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext; //1212121.jpg
            $image->move(public_path('upload/profile'), $imageName);

            $user->image = $imageName;
            $user->save();

            $manager = new ImageManager(Driver::class); 
            $img = $manager->read(public_path('upload/profile/'.$imageName));

            $img->cover(150,150);
            $img->save(public_path('upload/profile/thumb/'.$imageName));
        }

        return redirect()->route('account.profile')->with('success', 'Profile updated successfully.');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('account.login')->with('success', 'You have been logged out.');
    }

    public function myReviews(Request $request) {
        // Mengambil review dari database yang dimiliki oleh user yang sedang login
        $reviews = Review::with('book')
            ->where('user_id', Auth::user()->id)
            ->orderBy('created_at', 'DESC');
    
        // Memeriksa apakah ada kata kunci yang dikirim melalui request
        if ($request->has('keyword')) {
            $reviews = $reviews->where('review', 'like', '%' . $request->keyword . '%');
        }
    
        // Melakukan paginasi pada hasil query
        $reviews = $reviews->paginate(10);
    
        // Mengirim data review ke view 'account.my-reviews'
        return view('account.my-reviews.my-reviews', [
            'reviews' => $reviews
        ]);
    }
    
    public function editReview ($id) {
        $review = Review::where([
            'id' => $id,
            'user_id' => Auth::user()->id
        ])-> with('book')-> first();
        return view('account.my-reviews.edit-review', [
            'review' => $review
        ]);
    }

    public function updateReview ($id, Request  $request){

        $review = Review::findOrFail($id);

        $validator = Validator::make($request->all(),[
            'review' => 'required',
            'rating' => 'required'
        ]);

        if ($validator->fails()){
            return redirect()->route('account.myReviews.editReview',$id)->withInput()->withErrors($validator);
        }

        $review->review = $request->review;
        $review->rating = $request->rating;
        $review->save();

        session()->flash('success', 'Your review updated successfully');
        return redirect()->route('account.my-reviews');
    }

    public function deleteReview(Request $request){

        $id = $request->id;

        $review = Review::find($id);

        if ($review == null){
            return response()->json([
                'status' => false
            ]);
        }

        $review->delete();

        session()->flash('success','Review deleted successfully');
        
        return response()->json([
            'status' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
}
