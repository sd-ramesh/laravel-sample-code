<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\{User, User_detail};
use Auth, Image;



class UserController extends Controller
{

    public function __construct(){
        $this->middleware('auth');
    }

    public function index(){
        $users = User::with(['user_detail' => function ($query) {
            $query->with('state', 'country');
        }])->where('id', Auth::user()->id)->first();
        return view('user.dashboard', compact('users'));
    }

    public function addressUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
			'address' => 'required|string',  
			'city' => 'required|string',
            'zipcode' => 'required|min:4|max:8',
			'state' => 'required',
			'country' => 'required',
        ]);
        if($validator->fails()){
            return response()->json(array(
            'success' => false,
            'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }
        try
        {
            $users = User::findOrFail(Auth::user()->id);
            $postData = $request->all();
			$users->user_detail->address = $postData['address'];
			$users->user_detail->city = $postData['city'];
			$users->user_detail->zipcode = $postData['zipcode'];
			$users->user_detail->country_id = $postData['country'];
			$users->user_detail->state_id = $postData['state'];
            $users->push();       
            return response()->json(['success' => true, 'message' => 'Users address '.Config::get('constants.SUCCESS.UPDATE_DONE')]);

        } catch ( \Exception $e ) {
            return response()->json(['status' => 'error', 'message' => Config::get('constants.ERROR.TRY_AGAIN_ERROR')]);
        }
    }


    public function profileUpdate(Request $request) {
        if ($request->isMethod('get')) {
            $userDetail = User::with('user_detail')->find(Auth::user()->id);
            if(!$userDetail)
            return redirect()->route('userdashboard'); 
            return view('user.editProfile',compact('userDetail'));
        }
        else{
            $validator = Validator::make($request->all(),[
                'first_name' => 'required|string',  
                'last_name' => 'required|string',
                'mobile' => 'required|digits:10|unique:user_details,mobile,'.Auth::user()->id.',user_id',
            ]);
            if($validator->fails()){
                return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
                ), 400);
            }
            try
            {
                $users = User::findOrFail(Auth::user()->id);
                $postData = $request->all();
                $users->first_name = $postData['first_name'];
                $users->last_name = $postData['last_name'];
                $users->user_detail->mobile = $postData['mobile'];
                $users->push();       
                return response()->json(['success' => true, 'message' => 'Users details '.Config::get('constants.SUCCESS.UPDATE_DONE')]);

            } catch ( \Exception $e ) {
                return response()->json(['status' => 'error', 'message' => Config::get('constants.ERROR.TRY_AGAIN_ERROR')]);
            }
        }
    }

    public function pictureUpdate(Request $request) {
        if($request->hasFile('profile_picture')) {
            $allowedfileExtension=['jpg','png', 'webp', 'jpeg'];
            $file = $request->file('profile_picture');
            $extension = $file->getClientOriginalExtension();
            $check=in_array($extension,$allowedfileExtension);
            if($check) {
                $image_resize = Image::make($file)->resize( null, 90, function ( $constraint ) {
                    $constraint->aspectRatio();
                })->encode( $extension ); 
                $users_details = User_detail::where('user_id' , Auth::user()->id)->first();
                if($users_details == null) {
                    $users_details = User_detail::create([
                        'user_id' => Auth::user()->id,
                        'profile_picture'=>$image_resize,
                        'imagetype' => $extension,
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ]); 
                } else {
                    $users_details->update(['profile_picture'=>$image_resize, 'imagetype' => $extension, 'updated_at' => date('Y-m-d H:i:s')]);
                }
            } else {
                return redirect()->back()->with('status', 'error')->with('message', "Please select png, jpg or jpeg images.");
            }
        }
        else
        {
            return redirect()->back()->with('status', 'error')->with('message', Config::get('constants.ERROR.OOPS_ERROR'));
        }
        return redirect()->back()->with('status', 'success')->with('message', 'Profile Picture '.Config::get('constants.SUCCESS.UPDATE_DONE'));
    }

    public function passwordUpdate(Request $request) {
        if ($request->isMethod('get')) {
            return view('user.password');
        }
        else{
            $validatedData = $request->validate([
                'old_password' => 'required',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|same:password'
            ]);
            if (!(Hash::check($request->get('old_password'), Auth::user()->password))) {
                return redirect()->back()->with('status', 'error')->with('message', Config::get('constants.ERROR.PASSWORD_MISMATCH'));
            }
            if(strcmp($request->get('old_password'), $request->get('password')) == 0){
                return redirect()->back()->with('status', 'error')->with('message', Config::get('constants.ERROR.PASSWORD_SAME'));
            }
            
            //Change Password
            $user = Auth::user();
            $user->password = Hash::make($request->get('password'));
            $user->save();

            return redirect()->back()->with('status', 'success')->with('message', ' Password '.Config::get('constants.SUCCESS.UPDATE_DONE'));
        }
    }

    public function getRatings(Request $request) {
        if ($request->isMethod('get')) {
            $ratingCount = Company_review::where('user_id', Auth::user()->id)->count();
            $ratings = Company_review::with(['company', 'company_reply'])->withCount('company_reply')->where('user_id', Auth::user()->id)
            ->orderByDesc('id')->paginate(Config::get('constants.PAGINATION_NUMBER'));
            return view('user.rating', compact('ratings', 'ratingCount'));
        }
        else{
            return redirect()->back()->with('status', 'error')->with('message', ' Company Categories '.Config::get('constants.ERROR.OOPS_ERROR'));
        }
    }

    public function getReview($id = NULL) {
        $id = decrypt_userdata($id);
        $review = Company_review::with(['user'])->where('id', $id)->first();
        if(is_null($review)){
            return response()->json(["success"=>false, "msg"=> Config::get('constants.ERROR.OOPS_ERROR')], 200);
        }
        $reviewData = $reviewsdata = '';
        $reviewData = $review->review_data ? unserialize($review->review_data) : '';
        $message = csrf_field().
        '<input type="hidden" name="companyId" id="companyId" value="'.encrypt_userdata($review->company_id).'">';
        if($reviewData)
        $message .= '<input type="hidden" name="reviewId" id="reviewId" value="'.encrypt_userdata($id).'">';
        $message .= '<div class="popup-rating-overall-detail review-criteria">
            <div class="review-rating-left">
                <h3>Your Rating</h3>
                <ul>';
                    
                    foreach($this->criteriaList() as $key => $criteria){
                        $message .= '<li class="criteria" id="criteria-'.$key++.'">
                            <input type="hidden" name="criteria_name[]" value="'.$criteria->name.'">
                            <span class="criteria-name"><span>'.ucwords($criteria->name).':</span></span>
                            <ul class="inner-stars">';
                                for($i = 1; $i <= 5; $i++){
                                    if($reviewData && $reviewData[$criteria->name] >= $i)
                                    $message .= '<li><i class="fa fa-star active" data-rating="'.$i.'" ></i></li>';
                                    else
                                    $message .= '<li><i class="fa fa-star" data-rating="'.$i.'" ></i></li>';
                                }
                                if($reviewData)
                                $message .= '<input type="hidden" name="criteria_rating[]" class="rating-value" value="'.$reviewData[$criteria->name].'">';
                                else
                                $message .= '<input type="hidden" name="criteria_rating[]" class="rating-value" value="0">';
                                $message .= '
                            </ul>
                        </li>';
                    }
                    $message .= '<input type="hidden" name="stars" id="starsrating"  value="1">
                </ul>
            </div>
            <div class="review-rating-right">
                <h3>Your Overall Rating</h3>
                <div class="overall-rating-content">
                    <input type="hidden" name="overall_rating" id="overall-criteria-rating" value="'.$review['rate_point'].'">
                    <span class="overall-rating">'.$review['rate_point'].'</span>
                    <span class="outoff-rating">/5</span>
                </div>
            </div>
        </div>
        <div class="popup-review-text">
            <div class="form-group">
                <label for="review">Write Your Review<span class="req">*</span></label>
                <textarea class="form-control" placeholder="Write Review Here.." id="review-message" name="review" rows="4">'.$review['review'].'</textarea>
            </div>
            <div class="cost-drpdown-footer">
                <button type="button" class="btn clear-btn" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn apply-btn">Submit Review</button>
            </div>
        </div>';
        return response()->json(["success"=>true, "msg"=> $message], 200);
    }
}
