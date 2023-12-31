<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index() {
        $search = request('search');
        if($search){
            $posts = Post::where([
                ['title', 'like', '%'.$search.'%']
                ])->get();
        }else{
            $posts = Post::all();
        }
            return view('welcome', ['posts' => $posts, 'search' => $search]);
    }

    public function create() {
        return view('posts.create');
    }
    public function store(Request $request) {
        $post = new Post;
        $post->title = $request->title;
        $post->local = $request->local;
        $post->text = $request->text;
        $post->category = $request->category;
        // upload imagem
        if( $request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->getClientOriginalExtension();
            $imageName = md5($requestImage->getClientOriginalName()) . strtotime("now") .'.'. $extension;
            $request->image->move(public_path('/img/posts'), $imageName);
            $post->image = $imageName;
        }
        $user = auth()->user();
        $post->user_id = $user->id;
        $post->scheduled_for_deletion_at = Carbon::now()->addHours(24);
        $post->save();
        return redirect('/')->with('msg', 'Post criado com sucesso!');
    }
    public function show($id) {
        $post = Post::findOrFail($id);
        $postOwner = User::where('id', $post->user_id)->first()->toArray();
        return view('posts.show', ['post' =>$post, 'postOwner'=>$postOwner]);
    }

    public function dashboard() {
        $user = auth()->user();
        $posts = $user->posts;
        return view('posts.dashboard', ['posts' => $posts]);
    }

    public function destroy($id){
        Post::findorFail($id)->delete();
        return redirect('/dashboard')->with('msg','História excluída com sucesso!');
    }

    public function edit($id) {
        $user = auth()->user();
        $post = Post::findOrFail($id);
        if($user->id != $post->user->id){
            return redirect('/')->with('msg',"It's not your post!");
        }
        return view('posts.edit', ['post'=>$post]);
    }

    public function update(Request $request) {
        Post::findOrFail($request->id)->update($request->all());
        return redirect('/dashboard')->with('msg','A história foi atualizado!');
    }
    public function joinPost($id) {
        $user = auth()->user();
        $user->postsAsParticipant()->attach($id);//nao sei porque o erro
        return redirect('/')->with('msg','reação top!');
    }
    public function leavePost($id) {
        $user = auth()->user();
        $user->postsAsParking()->detach($id);
        return redirect('/')->with('msg','Não é mais top!');
    }
}
