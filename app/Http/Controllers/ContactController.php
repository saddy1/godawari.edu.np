<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact');
    }

  public function storeContact(Request $request)
{
    // Honeypot field is invisible to real visitors; only bots fill it in.
    // A submission faster than 3 seconds after the form rendered is also almost
    // certainly scripted. Pretend success either way so bots don't adapt.
    $renderedAt = (int) $request->input('form_rendered_at');
    if (filled($request->input('website')) || ($renderedAt && (time() - $renderedAt) < 3)) {
        return back()->with('contact_success', 'Thank you! Your message has been sent successfully. We will get back to you soon.');
    }

    if ($request->session()->get('contact_submitted')) {
        return back()->with('contact_success', 'You have already sent us a message. Our team will get back to you soon.');
    }

    $request->validate([
        'name'    => 'required|string|max:255',
        'phone'   => 'required|string|max:20',
        'email'   => 'nullable|email|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    ContactMessage::create($request->only(['name', 'phone', 'email', 'subject', 'message']));

    $request->session()->put('contact_submitted', true);

    return back()->with('contact_success', 'Thank you! Your message has been sent successfully. We will get back to you soon.');
}
}
