<?php
/*
Plugin Name: Recipe Content Generator
Description: A plugin to generate and customize recipes using Google Gemini or Claude AI API, Replicate, WP Recipe Maker and Rank Math.
Version: 3.7.6
Author: Gouzak
*/
// Add "Settings" link to plugin action links.
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'my_plugin_action_links');

function my_plugin_action_links($links) {
    $settings_link = '<a href="admin.php?page=recipe_content_generator_settings">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link); // Add the settings link at the beginning of the links array.
    return $links;
}
// Add settings menu
function cog_add_admin_menu()
{
    add_options_page(
        'Recipe Content Generator Settings',
        'Recipe Content Generator',
        'manage_options',
        'recipe_content_generator_settings',
        'cog_options_page'
    );
}
add_action('admin_menu', 'cog_add_admin_menu');

// Register settings
function cog_settings_init()
{
    register_setting('cogPlugin', 'cog_settings');

    // API Settings section
    add_settings_section(
        'cog_api_section',
        'API Settings',
        'cog_api_section_callback',
        'cogPlugin'
    );
    add_settings_section(
        'cog_txt_section',
        'Writing style Settings',
        'cog_txt_section_callback',
        'cogPlugin'
    );
    add_settings_field(
        'gpt_choice',
        'GPT Choice',
        'cog_select_field_render',
        'cogPlugin',
        'cog_api_section',
        array(
            'field' => 'gpt_choice',
            'options' => array(
                "gemini" => 'Gemini',
                "claude" => 'Claude',
            )
        )
    );
    add_settings_field(
        'gemini_api_key',
        'AI API Key',
        'api_key_field',
        'cogPlugin',
        'cog_api_section',
        ['name' => 'gemini_api_key']
    );

    add_settings_field(
        'ai_model',
        'AI Model',
        'cog_select_field_render',
        'cogPlugin',
        'cog_api_section',
        array(
            'field' => 'ai_model',
            'options' => array(
                '' => 'Select an AI Model'
            )
        )
    );
  
      // Replicate API Settings
    add_settings_field(
        'replicate_api_key',
        'Replicate API Key',
        'cog_text_field_render',
        'cogPlugin',
        'cog_api_section',
        array('field' => 'replicate_api_key')
    );

    add_settings_field(
        'replicate_model',
        'Replicate model',
        'cog_select_field_render',
        'cogPlugin',
        'cog_api_section',
        array(
            'field' => 'replicate_model',
            'options' => array(
                 'black-forest-labs/flux-1.1-pro' => 'flux-1.1-pro (25 images/$1)',
                'black-forest-labs/flux-1.1-pro-ultra' => 'flux-1.1-pro-ultra (16 images/$1)',
                'black-forest-labs/flux-dev' => 'flux-dev (40 images/$1)',
                'black-forest-labs/flux-pro' => 'flux-pro (18 images/$1)',
                'black-forest-labs/flux-schnell' => 'flux-schnell (333 images/$1)',
                'recraft-ai/recraft-v3' => 'recraft-v3 (25 images/$1)',
                'stability-ai/stable-diffusion-3' => 'stable-diffusion-3 (28 images/$1)',
                'stability-ai/stable-diffusion-3.5-large' => 'stable-diffusion-3.5-large (15 images/$1)',
                'stability-ai/stable-diffusion-3.5-large-turbo' => 'stable-diffusion-3.5-large-turbo (25 images/$1)',
                'stability-ai/stable-diffusion-3.5-medium' => 'stable-diffusion-3.5-medium (28 images/$1)',
            )
        )
    );


    add_settings_field(
        'tone_of_voice',
        'Tone of Voice',
        'cog_select_field_render',
        'cogPlugin',
        'cog_txt_section',
        array(
            'field' => 'tone_of_voice',
            'options' => array(
                "friendly" => 'Friendly',
                "professional" => 'Professional',
                "informational" => 'Informational',
                "transactional" => 'Transactional',
                "inspirational" => 'Inspirational',
                "neutral" => 'Neutral',
                "witty" => 'Witty',
                "casual" => 'Casual',
                "authoritative" => 'Authoritative',
                "encouraging" => 'Encouraging',
                "persuasive" => 'Persuasive',
                "poetic" => 'Poetic',
            )
        )
    );


    add_settings_field(
        'point_of_view',
        'Point of View',
        'cog_select_field_render',
        'cogPlugin',
        'cog_txt_section',
        array(
            'field' => 'point_of_view',
            'options' => array(
                "1" => 'First person singular (I, me, my, mine)',
                "2" => 'First person plural (we, us, our, ours)',
                "3" => 'Second person (you, your, yours)',
                "4" => 'Third person (he, she, it, they)'
            )
        )
    );


    add_settings_field(
        'humanize_text',
        'Humanize Text',
        'cog_select_field_render',
        'cogPlugin',
        'cog_txt_section',
        array(
            'field' => 'humanize_text',
            'options' => array(
                "1" => '5th grade, easily understood by 11-year-olds',
                "2" => '6th grade, easy to read. Conversational language',
                "3" => '7th grade, fairly easy to read',
                "4" => '8th & 9th grade, easily understood',
                "5" => '10th to 12th grade, fairly difficult to read',
                "6" => 'College, difficult to read',
                "7" => 'College graduate, very difficult to read',
                "8" => 'Professional, extremely difficult to read',
            )
        )
    );
 
    add_settings_field(
        'introductory_hook',
        'Introductory Hook',
        'cog_select_field_render',
        'cogPlugin',
        'cog_txt_section',
        array(
            'field' => 'introductory_hook',
            'options' => array(
                "1" => 'Question',
                "2" => 'Statistical or Fact',
                "3" => 'Quotation',
                "4" => 'Anecdotal or Story',
                "5" => 'Personal or Emotional',
                "random" => 'Random',
            )
        )
    );
    // Prompts Settings section
    add_settings_section(
        'cog_prompts_section',
        'AI Prompts Settings',
        'cog_prompts_section_callback',
        'cogPlugin'
    );
    add_settings_field(
        'all_in_one_prompt',
        'All in one Prompt',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'all_in_one_prompt')
    );
    add_settings_field(
        'internal_links',
        'Internal Links Prompt',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'internal_links')
    );
    add_settings_field(
        'article_images',
        'Images Prompt',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'article_images')
    );
    add_settings_field(
        'add_recipe_prompt',
        'Add Recipe Prompt',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'add_recipe_prompt')
    );
    add_settings_field(
        'outline_prompt',
        'Outline Generation Prompt',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'outline_prompt')
    );

    add_settings_field(
        'paragraphs_prompt',
        'Paragraphs Generation Prompt',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'paragraphs_prompt')
    );

    add_settings_field(
        'image_prompt',
        'Image Prompt Template',
        'cog_textarea_field_render',
        'cogPlugin',
        'cog_prompts_section',
        array('field' => 'image_prompt')
    );
}
add_action('admin_init', 'cog_settings_init');

// Settings sections callbacks
function cog_api_section_callback()
{
    echo '<p>Enter your API keys and select AI models</p>';
}

function cog_txt_section_callback()
{
    echo '<p>Enter Text writing style</p>';
}
function cog_prompts_section_callback()
{
    echo '<p>Customize the prompts used for generating content. Use placeholders: {{topic}}, {{title}}, {{meta}},{{tone_of_voice}},
    {{point_of_view}},
    {{humanize_text}},
    {{introductory_hook}}</p>';
}

// Field render functions
function cog_text_field_render($args)
{
    $options = get_option('cog_settings');
    $field = $args['field'];
    $value = isset($options[$field]) ? $options[$field] : '';
    echo "<input type='text' class='regular-text' name='cog_settings[$field]' value='" . esc_attr($value) . "'>";
}

function cog_select_field_render($args)
{
    $options = get_option('cog_settings');
    $field = $args['field'];
    $value = isset($options[$field]) ? $options[$field] : '';

    echo "<select name='cog_settings[$field]' value='$value'>";
    foreach ($args['options'] as $key => $label) {
        $selected = ($value == $key) ? 'selected' : '';
        echo "<option value='$key' $selected>$label</option>";
    }
    echo "</select>";
}

function get_point_of_view($index)
{
    $point_of_view_options = array(
        "1" => 'First person singular (I, me, my, mine)',
        "2" => 'First person plural (we, us, our, ours)',
        "3" => 'Second person (you, your, yours)',
        "4" => 'Third person (he, she, it, they)'
    );
    return $point_of_view_options[$index];
}
function get_introductory_hook($index)
{
    $introductory_hook__options = array(
        "1" => "Craft an intriguing question that immediately draws the reader's attention. The question should be relevant to the article's topic and evoke curiosity or challenge common beliefs. Aim to make the reader reflect or feel compelled to find the answer within the article.",
        "2" => "Begin with a surprising statistic or an unexpected fact that relates directly to the article's main topic. This hook should provide a sense of scale or impact that makes the reader eager to learn more about the subject.",
        "3" => "Use a powerful or thought-provoking quote from a well-known figure that ties into the theme of the article. The quote should set the tone for the article and provoke interest in the topic.",
        "4" => "Create a brief, engaging story or anecdote that is relevant to the article's main subject. This story should be relatable and set the stage for the main content.",
        "5" => "Write an emotionally resonant opening that connects personally with the reader. This could be a reflection, a personal experience, or an emotional appeal that aligns with the article's theme.",
    );
    if ($index == "random") {
        $index = random_int(1,5);
    }
    return $introductory_hook__options[$index];
}
function get_humanize_text($index)
{
    $options = array(
        "1" => '5th grade, easily understood by 11-year-olds',
        "2" => '6th grade, easy to read. Conversational language',
        "3" => '7th grade, fairly easy to read',
        "4" => '8th & 9th grade, easily understood',
        "5" => '10th to 12th grade, fairly difficult to read',
        "6" => 'College, difficult to read',
        "7" => 'College graduate, very difficult to read',
        "8" => 'Professional, extremely difficult to read',
    );

    return $options[$index];
}


function cog_textarea_field_render($args)
{
    $options = get_option('cog_settings');
    $field = $args['field'];
    $value = isset($options[$field]) ? $options[$field] : '';
    echo "<textarea class='large-text code' rows='10' name='cog_settings[$field]'>" . esc_textarea($value) . "</textarea>";
}

// Settings page
function cog_options_page()
{
    ?>
    <div class="wrap">
        <h1>Recipe Content Generator Settings</h1>

        <div id="api-settings" class="settings-tab">
            <form action='options.php' method='post'>
                <?php
                settings_fields('cogPlugin');
                do_settings_sections('cogPlugin');
                submit_button();
                ?>
            </form>
        </div>
    </div>

    <?php
}
function api_key_field($args) {
    $name = $args['name'];
    $options = get_option('cog_settings');
    $value = isset($options[$name]) ? esc_attr($options[$name]) : '';
    echo "<input type='text' name='cog_settings[$name]' value='$value' />";
}
 function fetch_claude_models() {
    if (!isset($_POST['api_key'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }
    $api_key = sanitize_text_field($_POST['api_key'] ?? '');

    if (empty($api_key)) {
        wp_send_json_error('API key is required.');
    }

    $claudeModels = fetch_from_api('https://api.anthropic.com/v1/models', $api_key);

    wp_send_json_success(['claudeModels' => $claudeModels]);
}
add_action('wp_ajax_fetch_claude_models', 'fetch_claude_models');

 function fetch_from_api($url, $api_key = null) {
    $response = wp_remote_get($url, array(
        'timeout' => 60,
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        ),
    ));
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}
// Initialize default settings
function cog_initialize_settings()
{
    $default_settings = array(
        'tone_of_voice' => 'informational',
        'humanize_text' => '4',
        'gpt_choice' => 'claude',
        'introductory_hook' => "random",
        "point_of_view" => '3',
        'outline_prompt' => "You are tasked with creating an SEO-friendly outline for an article between 1500 and 2000 words. A well-structured, SEO-optimized outline is crucial for creating content that ranks well in search engines and engages readers effectively.

Follow these guidelines to create an SEO-friendly outline:

1. Understand the topic and target audience:
<topic>
{{topic}}
</topic>

<target_audience>
English speakers between 20 and 80 years old, but should be able to understood by 9th grade and up.
</target_audience>

2. Structure your outline as follows:
   a. Introduction
   a. 3-5 main sections (H2)
   b. 2-3 subsections for each main section (H3)
   c. Conclusion

3. Conduct keyword research:
   - Identify 1 primary keyword and 2-3 secondary keywords related to the topic
   - Include these keywords naturally throughout the outline

4. Ensure the outline flows logically and covers the topic comprehensively
5. Remember to create an outline that is both informative and engaging for the target audience while optimizing for search engines.

6. Format your outline using this markdow, just one title by line, level 1 is represented by '-' level 2 by '--' level 3 by '---', followed by '|' and the title after that :
    <outline>
         -|title
         --|subtitle
         -|title
         --|subtitle
         ---|sub-subtitle
    </outline>

7. Keep in mind that the final article should be between 1500 and 2000 words. Adjust the number of sections and subsections accordingly.
8. put the article title beteween <title> and </title>
9. a meta description of 160 characters exactly, It should contain the main topic keyword, and put it between <meta> and </meta>
10. add a permalink that contain main keyword between and 75 characters MAX <permalink> and </permalink>
11. add related keywords in this format : <keywords>keyword1,keyword2,keyword3,.....</keywords>. put the main keyword in the begining.",

'paragraphs_prompt' => "You are a skilled content writer tasked with generating paragraphs for an article based on its outline. Your goal is to create high-quality, informative content that accurately represents each section of the article.
Here's the article information you'll be working with:

1.If the paragraph is an introduction it should be :
<introduction>
{{introductory_hook}}
</introduction>

2.Point of View:
<point_of_view>
{{point_of_view}}
</point_of_view>

3.Tone of voice : 
<tone_of_voice>
{{tone_of_voice}}
</tone_fo_voice>

4.Readability mode :
<readability mode>
{{humanize_text}}
</readability_mode>

5.Article Title:
<article_title>
{{title}}
</article_title>

6.Meta Description:
<meta_description>
{{meta}}
</meta_description>

Article Outlines are in this format \"<article_outline>[id|section_level|title],[id|section_level|title]<article_outline>\" :
<article_outline>{{sections}}</article_outline>

Your task is to generate a paragraph for each title in the outline. Follow these steps:

1. Carefully read the article title, meta description, and outline.

2. For each title in the outline:
   a. Consider the section level and how it relates to the overall article structure.
   b. Write a high-quality paragraph that accurately represents the title's content.
   c. Use HTML formatting to enhance readability and structure. This should include:
      - Using <table> tags for presenting data or comparisons
      - Using <blockquote> tags for notable quotes or emphasis.
      - Using <ul> or <ol> tags for lists where appropriate.
      - Using <strong> tags for important keywords.
   d. Ensure that the paragraph flows well with the rest of the article content.

3. Format your output as follows:
   <paragraphs>id|<p>[Your HTML-formatted paragraph here]</p>####id|<p>[Your HTML-formatted paragraph here]</p>####id|<p>[Your HTML-formatted paragraph here]</p>####<p>....</paragraphs>

   Where 'id' is a unique identifier for each paragraph (use the ids present in <article_outline> ).
4.Make the level 2 section paragrapghs 200 characters MAX.

Remember to maintain a cohesive narrative throughout the article, ensuring that each paragraph contributes to the overall message conveyed by the title and meta description.",
        'image_prompt' => "You are an AI specialist in creating detailed and vivid image prompts. Your task is to generate a photo prompt that represents the section title of an article while considering the context provided in the article's summary.

Here's the article summary:
<article_summary>
{{meta}}
</article_summary>

And here's the article title:
<section_title>
{{title}}
</section_title>

Before crafting the final image prompt, please think through the following steps:

<image_analysis>
1. Analyze the article summary and identify key themes, mood, and context.
2. Consider the article title and how it relates to the summary.
3. Identify key words or phrases from the title and summary.
4. List potential visual elements for each key word/phrase:
   - Main subject or focal point
   - Setting or background
   - Color palette
   - Lighting and atmosphere
   - Composition and perspective
5. Consider the tone and emotion of the article.
6. Brainstorm symbolic or metaphorical representations that could enhance the image.
7. Prioritize the most important visual elements to include in the prompt.
8. Consider how to make the image prompt unique and engaging.
9. Think about technical aspects of image generation:
   - Artistic style (e.g., photorealistic, illustration, abstract)
   - Specific techniques (e.g., depth of field, motion blur, high contrast)
10. If you want to include text in the photo put it between double quotes.
</image_analysis>

Now, using the insights from your image analysis process, create a detailed and vivid image prompt that represents the article title while considering the context from the summary. Your prompt should be descriptive enough to guide an image generation AI in creating a compelling and relevant image. Include details about the subject, setting, mood, colors, lighting, composition, and any symbolic elements.
- Add a very short seo friendly alt text for this image and put it between <alt_text> and </alt_text>.
- Put prompt text between <prompt> and </prompt>.

Please provide your final image prompt and alt text below:",
       "replicate_model" => "black-forest-labs/flux-schnell",
       "all_in_one_prompt" => "You are an SEO expert, a recipe writer, *and a sophisticated AI language model*. I own a recipe website targeting a U.S.-based audience, and I need you to write a complete and comprehensive SEO-optimized article directly, without intermediate steps or outlines. Follow these exact instructions:

       Instructions:
       **Originality and Plagiarism Prevention:**
       
       - start the article content that i will use with the separator so i can extract it later: START THE ARTICLE
       * Ensure that the generated article is as original as possible.
       * Minimize the risk of plagiarism by reformulating information and using a unique writing style.
       * Aim for a high degree of originality in all content.
       - The recipe part must provide step-by-step cooking instructions for this recipe with the measurements of ingredients to make it easy to read and follow.
       
       here is keyphrase: fried corn recipe
       
       Use the keyword naturally throughout the article, including in the title, headings, subheadings, introduction, and conclusion. Adhere strictly to Yoast SEO's keyword density recommendations.
       Keyword Density:
       Ideal Density: Between 0.5% and 3% of the total text.
       Examples:
       For a 1,000-word article: the main keyword can appear 5 to 30 times.
       Avoid keyword stuffing: Use synonyms and variations to enrich the text.
       Ensure keywords are integrated naturally and smoothly.
       
       Article Specifications:
       Length: The article must be between 2500 and 3000 words and make sure the word count is calculated exclusively for the article.
       Structure of the Article:
       Outline:
       Create a detailed professional outline before writing the article. Ensure the outline is rich, logical, and tailored to fully address the topic, with main headings and subheadings that cover all key aspects. Use the outline as a guide to ensure the article is well-structured and complete.
       
       Title and Content:
       Generate an SEO-optimized title as an H1 heading for the article 
       Use H2 tags for main paragraph headings and H3 tags for secondary subheadings.
       Insert the keyphrase or synonyms naturally in at least 5 H2 headings and H3 subheadings throughout the content.
       After the title, write an introductory description that summarizes the content of the article. 
       Write the article directly after the title and introductory description, without mentioning 'Title' explicitly.
       The content should follow the outline created and maintain a logical flow throughout.
       When you finish, make sure to include an H1 title at the very top of the article. Keep in mind that this article will be copied and pasted directly, so the structure should be clear and ready to use. Don’t forget the title (H1) above the article.
       Use the following structure for the outline:
       -Introduction
       -6 max of sections(H2) and between 2 to 3 subsections(H3) for each
       -FAQs (section title must be: FAQs about Keyphrase)
       -Conclusion
       Content and Formatting:
       The recipe part must provide step-by-step cooking instructions for this recipe with the measurements of ingredients to make it easy to read and follow.
       Write the article using Markdown formatting.
       Italicize the most important keywords to emphasize their relevance.
       Use bullet points where necessary for clarity and readability.
       Ensure all sentences are concise and use fewer than 20 words wherever possible. Split long sentences into shorter, clearer ones.
       Use plenty of transition words (e.g., 'however,' 'therefore,' 'in addition,' 'for example') to improve readability and flow. At least 40% of the sentences must contain transition words while keeping the content clear, fluid, and natural.
       Use additional SEO-optimized transition words, such as: 'accordingly', 'additionally', 'afterward', 'altogether', 'another', 'basically', 'because', 'chiefly', 'comparatively', 'consequently', 'conversely', 'equally', 'especially', 'eventually', 'explicitly', 'finally', 'further', 'furthermore', 'generally', 'hence', 'however', 'indeed', 'initially', 'likewise', 'meanwhile', 'moreover', 'nevertheless', 'nonetheless', 'particularly', 'specifically', 'subsequently', 'therefore', 'thus', 'undoubtedly', 'unquestionably', 'until', 'whereas', 'while.'
       
       Using Simpler Words:
       Write for readability. Use simple and clear words, ensuring the content is easy to understand. Avoid overly complex vocabulary, but maintain a professional tone.
       Review the following list of complex words and reduce their usage to less than 7% in the article. Replace them with simpler alternatives when possible without losing the original meaning and flow:
       Ingredients, Versatile, Essential, Vitamins, Calories, Probiotics, Digestion, Low carb, Nutrients, Balance, Flexible, Gluten-free, Lactose-free, Exercising, Selenium, Antioxidant, Potassium, Compliments, Vegetables, Satisfying, Cucumber, Tomatoes, Sprinkle, Shredded, Refreshing, Rich, Crackers, Whole grain, Protein-rich, Freshness, Elevated, Prosciutto, Sprinkling, Sunflower, Additional, Drizzling, Balsamic, Enhancements, Indulgent, Scrambled, Mushrooms, Hard-boiled, Proteins, Seasoning, Variations, Mayonnaise, Compatible Carbohydrates, Limiting Carbohydrates, Varieties, Macronutrients, Requirements, Vegetarians, Preferences, Additives, Digestive, Bloating, Contribute, Increased, Downside, Artificial, Preservatives, Intolerance, Conscious, Consumers, Riboflavin, Cognitive, Maintaining, Higher Carbs, Characteristic, Involuble, Diabetics, Sustained, Glycemic, Harmonious, Compliment, Sophisticated, Culinary, Pairings, Exploring, Featuring, Editions, Packaging, Seasonal, Limited Edition, Memorable, Exclusive, Expressing, Gratitude, Emphasizing, Timeless, Branding, Recognition, Recipients, Personalization, Enhances, Cost-Effective, Ordering Discount, Quantities, Businesses, Overspending, Strengthen, Partnership, Thoughtful, Collaboration, Encourage, Goodwill, Prioritize, Sourcing, Ingredient, Fair Trade, Suppliers, Sustainable, Recyclable, Minimalistic, Increasingly, Appealing, Environmentally Conscious, Choosing, Eco-Friendly, Environmental, Sustainably.
       
       SEO Guidelines:
       Write the article to fully comply with Yoast SEO guidelines for both SEO and readability.
       Use short paragraphs, active voice, and plenty of transition words to enhance readability.
       Use LSI keywords and NLP keywords related to the focus keyword.
       
       Frequently Asked Questions (FAQs):
       Use the following PAA (People Also Ask) questions to create a detailed FAQ section at the end of the article. **Do not add any additional FAQs and always ensure that there are only the PAA you provide below (maximum 4 PAA).**
       
       Why is my fried corn chewy?What is the best cooking technique for corn?How do you fry corn patti labelle?Is corn fry healthy?
       
       Post-Article Section:
       Always add a clear separator:
       END OF ARTICLE
       Below the separator, provide:
       Image Optimization (Alt Text, Title, Caption, Description):
       At the end of the article, provide recommendations for image metadata, including:
       Alt Text: Describe the image concisely and include the **exact focus keyword**. You can add a few words, but the exact keyphrase must be present.
       Title: Create a descriptive title for the image.
       Caption: Write a short, engaging caption that provides context for the image.
       Description: Add a detailed description, including relevant keywords and context about the image.
       
       Suggested Internal Links:
       List internal links that are relevant to the article's topic for manual addition.
       
       SEO Enhancements:
       SEO Title: Create an SEO-friendly title starting with the **exact focus keyword** (≤62 characters) and put it between <seoTitle> and </seoTitle>.
       Meta Description: Write an engaging description using the focus keyword (≤150 characters) and put it between <metadesc> and </metadesc>..
       Slug: Create a short, descriptive slug using the **exact focus keyword** and put it between <slug> and </slug>.
       Focus Keyphrase: Mention the focus keyphrase and include synonyms in the format <keywords>keyword1,keyword2,keyword3,.....</keywords> put the Focus Keyphrase in the beggining.. 
       
       Image Metadata Suggestions:
       Alt Text: Focus keyword included in a concise description.
       Title: Descriptive title for the image.
       Caption: Short and engaging caption related to the article.
       Description: Detailed image description including relevant keywords.
       
       MidJourney Prompts for Article Images:
       Give me 4 Midjourney prompts to generate 4 images for this article, and show me where to integrate them exactly. End them with “--ar 3:2” to get the appropriate dimensions.
       One of them is the featured image.",
       "more_words_prompt" => "You are an expert recipe writer and SEO specialist and a sophisticated AI language model. You will expand the provided article to reach at least 3000 words, while adhering to the existing formatting and SEO guidelines, and without expanding the FAQ section.

       **I. Input:**
       
       *   **Existing Article:** {{article}}
       **II. Output Structure:**
       
          The output must be provided in the following order:
       
       1.  **Start Separator:** START THE ARTICLE
       2.  **Expanded Article Content:** Including all required formatting and structural elements and always starting with the H1 tag
       3.  **End Separator:** END OF ARTICLE",
       "add_recipe_prompt" => 'Based on this article {{article}} give me the recipe in this format Recipe format
       {
       "id": 41,
       "type": "food",
       "image_url": "https://demo.wprecipemaker.com/wp-content/uploads/2018/10/baked-beer-cheese-724216.jpg",
       "pin_image_url": "https://demo.wprecipemaker.com/wp-content/uploads/2018/10/baked-beer-cheese-724216.jpg",
       "name": "Amazing Vegetable Pizza",
       "summary": "<p>Every night can be pizza night, if you ask me. Just throw whatever vegetable leftovers you have on there and enjoy!</p>",
       "author_display": "disabled",
       "author_name": "",
       "author_link": "",
       "cost": "",
       "servings": "2",
       "servings_unit": "pizzas",
       "prep_time": "15",
       "prep_time_zero": "",
       "cook_time": "15",
       "cook_time_zero": "",
       "total_time": "60",
       "custom_time": "30",
       "custom_time_zero": "",
       "custom_time_label": "Resting Time",
       "tags": {
       "course": [
       "Pizza"
       ],
       "cuisine": [
       "Italian"
       ],
       "keyword": [
       "Vegetarian"
       ],
       "difficulty": []
       },
       "equipment": [
       {
       "name": "Pizza Stone"
       }
       ],
       "ingredients_flat": [
       {
       "name": "Pizza Dough",
       "type": "group"
       },
       {
       "amount": "1",
       "unit": "cup",
       "name": "water",
       "notes": "lukewarm",
       "converted": {
       "2": {
       "amount": "250",
       "unit": "ml"
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "2",
       "unit": "cups",
       "name": "all-purpose flour",
       "notes": "",
       "converted": {
       "2": {
       "amount": "500",
       "unit": "g"
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "1",
       "unit": "tsp",
       "name": "instant yeast",
       "notes": "",
       "converted": {
       "2": {
       "amount": "1",
       "unit": "tsp"
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "1",
       "unit": "tsp",
       "name": "salt",
       "notes": "",
       "converted": {
       "2": {
       "amount": "1",
       "unit": "tsp"
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "1",
       "unit": "tsp",
       "name": "sugar",
       "notes": "",
       "converted": {
       "2": {
       "amount": "1",
       "unit": "tsp"
       }
       },
       "type": "ingredient"
       },
       {
       "name": "Pizza Toppings",
       "type": "group"
       },
       {
       "amount": "",
       "unit": "",
       "name": "red sauce",
       "notes": "",
       "converted": {
       "2": {
       "amount": "",
       "unit": ""
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "1/4",
       "unit": "",
       "name": "red onion",
       "notes": "",
       "converted": {
       "2": {
       "amount": "0.25",
       "unit": ""
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "1/4",
       "unit": "",
       "name": "green pepper",
       "notes": "",
       "converted": {
       "2": {
       "amount": "0.25",
       "unit": ""
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "1/4",
       "unit": "",
       "name": "red pepper",
       "notes": "",
       "converted": {
       "2": {
       "amount": "0.25",
       "unit": ""
       }
       },
       "type": "ingredient"
       },
       {
       "amount": "",
       "unit": "",
       "name": "rosemary",
       "notes": "",
       "converted": {
       "2": {
       "amount": "",
       "unit": ""
       }
       },
       "type": "ingredient"
       }
       ],
       "instructions_flat": [
       {
       "text": "<p>Combine the water, yeast and sugar in a bowl. Rest for 5 minutes.</p>",
       "type": "instruction",
       "image_url": ""
       },
       {
       "text": "<p>Combine the flour and salt.</p>",
       "type": "instruction",
       "image_url": ""
       },
       {
       "text": "<p>Add the yeast mixture and knead until you get a soft ball.</p>",
       "type": "instruction",
       "image_url": ""
       },
       {
       "text": "<p>Place in a bowl and cover. Let rise for 30 minutes.</p>",
       "type": "instruction",
       "image_url": ""
       },
       {
       "text": "<p>Divide the dough and form pizzas.</p>",
       "type": "instruction",
       "image_url": "https://demo.wprecipemaker.com/wp-content/uploads/2018/10/bake-baker-bakery-1251179.jpg"
       },
       {
       "text": "<p>Top the pizzas with sauce and vegetables, cook for 15 minutes on the pizza stone.</p>",
       "type": "instruction",
       "image_url": ""
       }
       ],
       "video_embed": "",
       "notes": "<p>Feel free to swap any of the topping in the ingredient list with whatever you have lying around. It will probably taste just as amazing!</p>\n",
       "nutrition": {
       "calories": 482,
       "carbohydrates": 101,
       "protein": 14,
       "fat": 1,
       "saturated_fat": 0,
       "cholesterol": 0,
       "sodium": 1174,
       "potassium": 230,
       "fiber": 4,
       "sugar": 4,
       "vitamin_a": 520,
       "vitamin_c": 32,
       "calcium": 19,
       "iron": 5.8
       },
       "custom_fields": {
       "inspiration": ""
       },
       "ingredient_links_type": "global"
       } and use the {{topic}} as a title',
       "internal_links" => "Based on these articles: {{posts}}

       Choose the most appropriate one that I can include smoothly as an internal link in this article. Give me a new sentence with the link and show me where to add it exactly in this
       article {{article}}",
       "article_images" => 'Based on this article {{article}},
       create 4 detailed and vivid mid journey image prompts to generate 4 images for the article. Your prompts should be descriptive enough to guide an image generation AI in creating a compelling and relevant image. Include details about the subject, setting, mood, colors, lighting, composition, and any symbolic elements. Include the dimensions “--ar 3:2” at the end of each prompt. Identify one prompt as the featured image.

        Image Metadata: For each image provide:

        Alt Text: A concise description with the exact focus keyword  {{topic}} (you can add a few more words, but the exact keyphrase must be present).

        Title: A descriptive title.

        Caption: A short, engaging caption that provides context.

        Description: A detailed description including relevant keywords and context.

        Emplacement: Show exactly where to integrate the generated images if its after a title mention just the title if its an featured image write featued

        An array of data in this format :

        [ { ”MidjourneyPrompt”: "", ”AltText”: "", ”Title”: "", ”Caption”: "", ”Description”: "T", "Emplacement": "" }, { ”MidjourneyPrompt”: "", ”AltText”: "", ”Title”: "", ”Caption”: "", ”Description”: "T", "Emplacement": "" }, { ”MidjourneyPrompt”: "", ”AltText”: "", ”Title”: "", ”Caption”: "", ”Description”: "T" , "Emplacement": ""}, { ”MidjourneyPrompt”: "", ”AltText”: "", ”Title”: "", ”Caption”: "", ”Description”: "T" , "Emplacement": ""}, ];
        The response needs to be just the array dont incude any other text
       '
    );

    // Only set default settings if they don't exist
    if (!get_option('cog_settings')) {
        update_option('cog_settings', $default_settings);
    }
}
register_activation_hook(__FILE__, 'cog_initialize_settings');

// Enqueue JavaScript and CSS files
function cog_enqueue_scripts()
{
    wp_enqueue_script(
        'cog-script',
        plugins_url('/js/cog-script.js', __FILE__),
        array('jquery', 'wp-blocks', 'wp-editor'),
        '14.97',
        true
    );
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

    $settings = get_option('cog_settings');
    wp_localize_script('cog-script', 'cog_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'settings' => $settings
    ));
}
add_action('admin_enqueue_scripts', 'cog_enqueue_scripts');

// Add custom meta box in the post editor
function cog_add_meta_boxes() {
    cog_add_meta_box();
    cog_add_internal_links_box();
    cog_add_images_box();
}
function cog_add_meta_box() {
    add_meta_box(
        'cog_meta_box',                     // Meta box ID
        'Recipe Content Generator',         // Title of the meta box
        'cog_meta_box_callback',            // Callback function for rendering content
        'post',                             // Post type
        'side'                              // Position ('side' means it will appear in the sidebar)
    );
}  
function cog_add_images_box() {
    add_meta_box(
        'cog_meta_box_images',                     // Meta box ID
        'Recipe Content Generator - Images',         // Title of the meta box
        'cog_images_box_callback',            // Callback function for rendering content
        'post',                             // Post type
        'normal'                              // Position ('side' means it will appear in the sidebar)
    );
} 
// // Add custom meta box for Internal Links
function cog_add_internal_links_box() {
    add_meta_box(
        'cog_meta_box_internal_links',      // Meta box ID (use a unique ID for each box)
        'Recipe Content Generator - Internal Links',  // Title of the meta box
        'cog_internal_links_box_callback',  // Callback function for rendering content
        'post',                             // Post type
        'normal'                            // Position ('normal' means it will appear in the main content area)
    );
}

add_action('add_meta_boxes', 'cog_add_meta_boxes');
function cog_internal_links_box_callback(){
    ?>
    <style>
    .ai_button_urls {
            width: auto !important;
        }

        .cog-internal-links-container {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: auto;
            margin: 0 auto;
        }

        /* Align label and toggle in one row */
        .ai_outline {
            display: block;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            width: 100%;
        }

        .ai_label {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }

        /* Toggle Switch Styling */
       
        .ai_outline .toggle-group {
    display: flex;
    gap: 20px; /* Space between toggles */
    align-items: center; /* Align items vertically */
}

.ai_outline .toggle-container {
    display: flex;
    align-items: center;
    gap: 8px; /* Space between switch and label */
}

.ai_outline .toggle-switch {
    position: relative;
    width: 40px;
    height: 20px;
}

.ai_outline .toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.ai_outline .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 20px;
}

.ai_outline .slider::before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

.ai_outline input:checked + .slider {
    background-color: #9dafa3;
}

.ai_outline input:checked + .slider::before {
    transform: translateX(20px);
}

.ai_outline .toggle-label {
    font-size: 14px;
    color: #333;
}

    </style>
    <div class="ai_outline">
    <div>
        <label class="ai_label" for="cog_image_url">Internal Links</label>

        <div class="toggle-group">
            <div class="toggle-container">
                <label class="toggle-switch">
                    <input type="checkbox" id="include_drafts" name="include_drafts">
                    <span class="slider"></span>
                </label>
                <span class="toggle-label">Include Draft Posts</span>
            </div>
        </div>

        <button type="button" class="ai_button_common ai_button_urls ai_button_gemini" id="cog_suggest_links_button">
            Generate Internal Links
        </button>

        <div id="cog_internal_output"></div>
        <div id="cog_internal_links_output" class="cog-internal-links-container" hidden="true"></div>
    </div>
</div>

    <?php
}
function cog_images_box_callback(){
    ?>
    <style>
        /* Style for action buttons */
        .icon-button {
            background-color: #563b26;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s ease-in-out;
            margin: 5px;
        }
        .copy-button {
            background-color: transparent;
            border: 0px;
            color: #563b26;
            cursor: pointer;
        }
        .copy-button:hover {
            color: #563b26a3;
        }
        /* Button hover effect */
        .icon-button:hover {
            background-color: #563b26a3;
        }

        /* Icon styling */
        .icon-button i {
            pointer-events: none;
        }

        /* Preview container styling */
        .preview-container {
            display: none; /* Initially hidden */
            align-items: center;
            justify-content: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            width: 160px;
            height: auto;
            overflow: hidden;
            margin-top: 10px;
        }

        /* Image preview styling */
        .preview-image {
            max-width: 150px;
            border-radius: 8px;
            box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.2);
        }

        #cog_images_result_output  table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Styling the table headers */
        #cog_images_result_output th {
            background-color: #9dafa3;
            color: white;
            text-align: left;
            padding: 12px;
            font-size: 16px;
        }
        
        /* Styling the table data cells */
        #cog_images_result_output td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        /* Alternate row colors for better readability */
        #cog_images_result_output tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Hover effect for rows */
        #cog_images_result_output tr:hover {
            background-color: #eaeaea;
        }

    </style>
     <div class="ai_outline">
        <div><label class="ai_label" for="cog_image_url">Images</label></div>
        <button type="button" class="ai_button_common ai_button_urls ai_button_gemini" id="cog_generate_images_button">Generate Images</button>
        
        <div id="cog_images_output"></div>
        <div id="cog_images_result_output" class="cog-images-container"></div>
    </div>
    <?php
}
function cog_meta_box_callback()
{
    ?>
    <style>
        .ai_input {
            border: 1px solid #949494;
            border-radius: 2px;
            box-shadow: 0 0 0 #0000;
            cursor: text;
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen-Sans, Ubuntu, Cantarell, Helvetica Neue, sans-serif;
            font-size: 16px;
            line-height: normal;
            padding: 0;
            transition: box-shadow .1s linear;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .ai_outline {
            margin: 15px 0px;
            padding: 5px;
        }

        #cog_output {
            text-align: center;
            font-size: 20px;
            color: #179c00;
        }

        .ai_label {
            font-size: 11px;
            font-weight: 500;
            line-height: 1.4;
            text-transform: uppercase;
            display: block;
            margin-bottom: calc(8px);
            padding: 0px;
        }

        #cog_ai_image_output {
            margin-top: 15px;
        }

        .ai_button {
            background-color: #563b26;
            border: 1px solid #563b26;
        }
        .ai_button:hover {
            background-color: #452e1c;
            border: 1px solid #452e1c;
        }
        .ai_button_common:hover {
            cursor: pointer;
        }
        .ai_button_common {
            padding: 9px;
            font-size: 14px;
            border-radius: 10px;
            color: white;
            margin-top: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            outline-offset: -1px;
            overflow: hidden;
            transition: all .1s ease-out;
            width: 90%;
        }
        .ai_button_gemini {
            background-color: #9dafa3;
            border: 1px solid #9dafa3;
        }
        .ai_button_gemini:hover{
            background-color: #647068;
            border: 1px solid #647068;
        }
        
    </style>
    <div class="ai_outline">
        <label class="ai_label" for="cog_topic">Topic</label>
        <input type="text" class="ai_input" id="cog_topic" name="cog_topic" />
        <label class="ai_label" for="cog_topic">People Also Asked (PAA)</label>
        <textarea type="text" class="ai_input" id="cog_paa" name="cog_paa"></textarea>
        <button type="button" class="ai_button_common ai_button_gemini" id="cog_generate_all_button">Generate Article</button>
        <button type="button" class="ai_button_common ai_button_gemini" id="cog_add_recipe_button">Add WP Recipe Maker</button>
        <!-- <button type="button" class="ai_button_common ai_button_gemini" id="cog_add_section_button">Add Section</button> -->
        <div id="cog_output"></div>
    </div>
   
    <div class="ai_outline">
        <div><label class="ai_label" for="cog_image_url">Paragraphs</label></div>
        <button type="button" class=" ai_button_common ai_button" id="cog_generate_button">Generate Outline</button>
        <button type="button" class="ai_button_common ai_button" id="cog_generate_text_button">Generate All</button>
        <button type="button" class="ai_button_common ai_button" id="cog_generate_selected_text_button">Generate Selected</button>
        <div id="cog_text_output"></div>
    </div>
    <div class="ai_outline">
        <label class="ai_label" for="cog_ai_featured_image">Featured image</label>
        <button type="button" class="ai_button_common  ai_button" id="cog_generate_featured_image_button">Generate featured image</button>
        <div id="cog_ai_featured_image_output"></div>
        <label class="ai_label" for="cog_featured_image_url">Image URL</label>
        <input type="text" class="ai_input" id="cog_featured_image_url" name="cog_featured_image_url" />
  <label class="ai_label" for="image_featured_alt_text">Image alt text</label>

        <input type="text" class="ai_input" id="image_featured_alt_text" name="image_featured_alt_text" />
        <button type="button" class="ai_button_common  ai_button" id="cog_upload_featured_image_button">Upload and set</button>
    </div>

    <div class="ai_outline">
        <label class="ai_label" for="cog_ai_image">Create image for title</label>
        <div style="margin-top: 10px;">
        <label  class="ai_label"  for="cog_image_ratio">Ratio</label>

            <input type="radio" id="cog_image_ratio_169" name="cog_image_ratio" value="169" checked>
            <label for="cog_image_ratio_169">16:9</label>
            <input type="radio" id="cog_image_ratio_11" name="cog_image_ratio" value="11">
            <label for="cog_image_ratio_11">1:1</label>
            <input type="radio" id="cog_image_ratio_43" name="cog_image_ratio" value="43">
            <label for="cog_image_ratio_43">4:3</label>
        </div>
    
   
        <button type="button" class="ai_button_common ai_button" id="cog_generate_image_button">Generate image for this title</button>
        <div id="cog_ai_image_output"></div>
    </div>
    <div class="ai_outline">
        <label class="ai_label" for="cog_image_url">Enter Image URL</label>
        <input type="text" class="ai_input" id="cog_image_url" name="cog_image_url" />
        <label class="ai_label" for="image_alt_text">Image alt text</label>

        <input type="text" class="ai_input" id="image_alt_text" name="image_alt_text" />
        <button type="button" class="ai_button_common ai_button" id="cog_upload_image_button">Upload and insert</button>
        <div id="cog_image_output"></div>
    </div>

    <?php
}

// Update chatWithAi function to use settings
function chatWithAi($prompt)
{
    $settings = get_option('cog_settings');
    $api_key = $settings['gemini_api_key'];
    $model = $settings['ai_model'];
    $gpt = $settings['gpt_choice'];
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
    if($gpt == "gemini") {
        $response = wp_remote_post(
            $url,
            array(
                'timeout' => 60,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(
                    array(
                        'contents' => array(
                            array(
                                'parts' => array(
                                    array(
                                        'text' => $prompt
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($response)) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
    } else {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'max_tokens' => 8192,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ))
        ));
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if(!empty($data['content'][0]['text'])) {
            return $data['content'][0]['text'];
        }
    }
    wp_send_json_error(['message' => 'API request failed', "gpt" => $gpt, "data" => $data]);
}

function extractTextBetweenTags($text, $startTag, $endTag)
{
    $start = strpos($text, $startTag);
    if ($start === false) {
        return ''; // Return empty if start tag is not found
    }
    $start += strlen($startTag); // Move start position to the end of the start tag

    $end = strpos($text, $endTag, $start);
    if ($end === false) {
        return ''; // Return empty if end tag is not found
    }

    return substr($text, $start, $end - $start); // Extract and return the text
}

function cog_upload_image_from_url()
{
    if (!isset($_POST['image_url'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }
    $alt = isset($_POST['alt']) ? $_POST['alt'] : '';

    $image_url = esc_url_raw($_POST['image_url']);
    $attachment = upload_image_from_url($image_url, $alt);

    if (is_wp_error($attachment)) {
        wp_send_json_error(['message' => $attachment->get_error_message()]);
    } else {
        wp_send_json_success(['attachment_id' => $attachment['attachment_id'], 'attachment_url' => $attachment['attachment_url']]);
    }
}
add_action('wp_ajax_cog_upload_image_from_url', 'cog_upload_image_from_url');

function generateRandomImageName($extension)
{
    // Generate a unique ID
    $uniqueId = uniqid();
    // Optionally add a random number
    $randomNumber = random_int(1000, 9999);
    // Combine the unique ID and random number with the specified extension
    return $uniqueId . '_' . $randomNumber . '.' . $extension;
}



// Helper function to upload image from a URL
function upload_image_from_url($image_url, $alt_text = '')
{
    $image_data = file_get_contents($image_url);
    if (!$image_data) {
        return new WP_Error('image_download_failed', 'Failed to download image from URL.');
    }

    $filename = basename($image_url);
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . generateRandomImageName('webp');
    file_put_contents($file_path, $image_data);

    $filetype = wp_check_filetype($filename, null);
    $attachment_data = array(
        'guid' => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => $filetype['type'],
        'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Insert the attachment
    $attach_id = wp_insert_attachment($attachment_data, $file_path);
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Set the alt text
    if (!empty($alt_text)) {
        update_post_meta($attach_id, '_wp_attachment_image_alt', $alt_text);
    }

    return array('attachment_id' => $attach_id, 'attachment_url' => wp_get_attachment_url($attach_id));
}


// Update generate_image_with_replicate_api function to use settings
function generate_image_with_replicate_api($prompt, $ratio)
{
    $settings = get_option('cog_settings');
    $api_token = $settings['replicate_api_key'];

    $url = "https://api.replicate.com/v1/models/" . $settings['replicate_model'] . "/predictions";

    $data = [
        'input' => [
            'prompt' => $prompt,
            'aspect_ratio' => $ratio,
            'seed' => mt_rand(1, 10000),
        ]
    ];

    $headers = [
        'Authorization' => 'Bearer ' . $api_token,
        'Content-Type' => 'application/json',
        'Prefer' => 'wait',
    ];

    $response = wp_remote_post($url, [
        'headers' => $headers,
        'body' => json_encode($data),
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return 'Request failed: ' . $response->get_error_message();
    }

    $response_data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_data['output']) && !empty($response_data['output'][0])) {
        return $response_data['output'][0];
    }
    return new WP_Error('image_generating_failed', 'Failed to generate image.');
}


function prepareChatText($prompt)
{
    $settings = get_option('cog_settings');

    $prompt = str_replace('{{tone_of_voice}}', $settings['tone_of_voice'], $prompt);
    $prompt = str_replace('{{point_of_view}}', get_point_of_view($settings['point_of_view']), $prompt);
    $prompt = str_replace('{{humanize_text}}', get_humanize_text($settings['humanize_text']), $prompt);
    $prompt = str_replace('{{introductory_hook}}', get_introductory_hook($settings['introductory_hook']), $prompt);
    return $prompt;

}
function cog_generate_custom_outline()
{
    if (!isset($_POST['topic'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }

    $topic = sanitize_text_field($_POST['topic']);
    $settings = get_option('cog_settings');
    $response = chatWithAi(prepareChatText(str_replace('{{topic}}', $topic, $settings['outline_prompt'])));

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
        return;
    }


    if (isset($response)) {
            $text = $response;
            $outline = extractTextBetweenTags($text, '<outline>', '</outline>');
            $title = extractTextBetweenTags($text, '<title>', '</title>');
            $meta = extractTextBetweenTags($text, '<meta>', '</meta>');
            $permalink = extractTextBetweenTags($text, '<permalink>', '</permalink>');
            $keywords = extractTextBetweenTags($text, '<keywords>', '</keywords>');
            wp_send_json_success(['outline' => $outline, 'title' => $title, 'meta' => $meta, 'permalink' => $permalink, 'keywords' => $keywords]);
        } else {
            wp_send_json_error(['message' => 'Failed to retrieve outline from Gemini API response']);
        }
}
add_action('wp_ajax_generate_custom_outline', 'cog_generate_custom_outline');

function cog_generate_paragraphs()
{
    if (!isset($_POST['title'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }
    if (!isset($_POST['meta'])) {
        wp_send_json_error(['message' => 'Please write a meta description first']);
        return;
    }
    if (!isset($_POST['sections'])) {
        wp_send_json_error(['message' => 'Please write sections first']);
        return;
    }

    $title = sanitize_text_field($_POST['title']);
    $meta = sanitize_text_field($_POST['meta']);
    $sections = sanitize_text_field($_POST['sections']);
    $settings = get_option('cog_settings');

   $response = chatWithAi(prepareChatText(str_replace(
        ['{{title}}', '{{meta}}', '{{sections}}'],
        [$title, $meta, $sections],
        $settings['paragraphs_prompt']
    )));


    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
         return;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);


    if (isset($response)) {
         $text = $response;
            $paragraphs = extractTextBetweenTags($text, '<paragraphs>', '</paragraphs>');
            wp_send_json_success(['paragraphs' => str_replace("\n", "", $paragraphs)]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate paragraphs from Gemini API response']);
        }

}
add_action('wp_ajax_generate_paragraphs', 'cog_generate_paragraphs');

function cog_generate_all_article()
{
    if (!isset($_POST['topic'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }

    $topic = sanitize_text_field($_POST['topic']);
    $paa = sanitize_text_field($_POST['paa']);
    $settings = get_option('cog_settings');
    
    $response = chatWithAi(prepareChatText(str_replace(['{{topic}}', '{{paa}}' ], [$topic, $paa], $settings['all_in_one_prompt'])));

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
        return;
    }

     $data = json_decode(wp_remote_retrieve_body($response), true);


    if (isset($response)) {
            $text = $response;
            $article = extractTextBetweenTags($text, 'START THE ARTICLE', 'END OF ARTICLE');
            $title = extractTextBetweenTags($text, '<H1>', '</H1>');
            $seoTitle = extractTextBetweenTags($text, '<seoTitle>', '</seoTitle>');
            $metadesc = extractTextBetweenTags($text, '<metadesc>', '</metadesc>');
            $slug = extractTextBetweenTags($text, '<slug>', '</slug>');
            $keywords = extractTextBetweenTags($text, '<keywords>', '</keywords>');
            $images = extractTextBetweenTags($text, '<ImageGuidelines>', '</ImageGuidelines>');
            wp_send_json_success([
                'title' => $title,
                'article' => str_replace("\n", "", $article),
                'seoTitle' => $seoTitle,
                'metadesc' => $metadesc,
                'slug' => $slug,
                'keywords' => $keywords,
                'images' => $images,
                'text' => $response,
                'gemini' => $settings['gpt']
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to retrieve outline from Gemini API response']);
        }

}
add_action('wp_ajax_generate_all_article', 'cog_generate_all_article');
function cog_generate_recipe()
{
    if (!isset($_POST['topic']) or !isset($_POST['article']) ) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }

    $topic = sanitize_text_field($_POST['topic']);
    $article = sanitize_text_field($_POST['article']);
    $settings = get_option('cog_settings');
    
    $response = chatWithAi(prepareChatText(str_replace(['{{topic}}', '{{article}}' ], [$topic, $article], $settings['add_recipe_prompt'])));

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
        return;
    }

     $data = json_decode(wp_remote_retrieve_body($response), true);


    if (isset($response)) {
            wp_send_json_success([
                'recipe' => str_replace("\n", "", $response)
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to retrieve outline from Gemini API response']);
        }

}
add_action('wp_ajax_generate_recipe', 'cog_generate_recipe');
function cog_generate_more_words()
{
    if (!isset($_POST['article'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }

    $article = sanitize_text_field($_POST['article']);
    $settings = get_option('cog_settings');
    
    $response = chatWithAi(prepareChatText(str_replace('{{article}}', $article, $settings['more_words_prompt'])));

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
        return;
    }

     $data = json_decode(wp_remote_retrieve_body($response), true);


    if (isset($response)) {
            $text = $response;
            $article = extractTextBetweenTags($text, 'START THE ARTICLE', 'END OF ARTICLE');
            wp_send_json_success([
                'article' => str_replace("\n", "", $article),
                'text' => $text,
                "prompt" => $settings['more_words_prompt']
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to retrieve outline from Gemini API response']);
        }

}
add_action('wp_ajax_generate_more_words', 'cog_generate_more_words');

function cog_generate_image_from_ai()
{
    if (!isset($_POST['title'])) {
        wp_send_json_error(['message' => 'Invalid input']);
        return;
    }
    if (!isset($_POST['meta'])) {
        wp_send_json_error(['message' => 'Please write a meta description first']);
        return;
    }
    $ratio = isset($_POST['ratio']) ? $_POST['ratio'] : '16:9';
    $title = $_POST['title'];
    $meta = $_POST['meta'];
    $settings = get_option('cog_settings');

    $response = chatWithAi(prepareChatText(str_replace(['{{title}}', '{{meta}}'], [$title, $meta], $settings['image_prompt'])));


    if (is_wp_error($response)) {
      wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
        return;
    }
     $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response)) {
          $text = $response;
        $prompt = extractTextBetweenTags($text, '<prompt>', '</prompt>');
        $alt = extractTextBetweenTags($text, '<alt_text>', '</alt_text>');
        $image = generate_image_with_replicate_api($prompt, $ratio);


           if (is_wp_error($image)) {
            wp_send_json_error(['message' => $image->get_error_message()]);
             return;
        } else {
            wp_send_json_success(['image_url' => $image, 'alt' => $alt]);
        }

    } else {
         wp_send_json_error(['message' => 'Failed to generate prompt from Gemini API response']);
    }


}
add_action('wp_ajax_cog_generate_image_from_ai', 'cog_generate_image_from_ai');
function cog_fetch_internal_links() {
    if (!isset($_POST['article']) || !isset($_POST['posts'])) {
        wp_send_json_error(['message' => 'Missing required data.']);
    }

    $article_content = sanitize_text_field($_POST['article']);
    $link = sanitize_text_field($_POST['link']);
    $posts = json_decode(stripslashes($_POST['posts']), true); // Decode JSON from JavaScript
    $settings = get_option('cog_settings');

    if (empty($posts)) {
        wp_send_json_error(['message' => 'No existing posts found.']);
    }
    
    $response = chatWithAi(prepareChatText(str_replace(['{{article}}', '{{posts}}', '{{link}}'], [$article_content, json_encode($posts), $link], $settings['internal_links'])));


    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Failed to connect to Google AI.']);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    // if (!isset($body['suggestions'])) {
    //     wp_send_json_error(['message' => 'No suggestions found.']);
    // }

    $suggestions = [];
    foreach ($body['suggestions'] as $suggestion) {
        $suggestions[] = [
            'anchor' => $suggestion['anchor_text'],
            'url'    => $suggestion['internal_link']
        ];
    }
    if (isset($response)) {
        $text = $response;
        wp_send_json_success(['links' => $text]);
    }
}

add_action('wp_ajax_fetch_internal_links', 'cog_fetch_internal_links');

/**
 * Uploads an image from a file, converts it to WebP, and saves it to the media library.
 *
 * @param array $file The uploaded file data.
 * @param string $alt The alt text for the image.
 * @param string $title The title of the image.
 * @param string $caption The caption for the image.
 * @param string $description The description of the image.
 * @return array|WP_Error Array containing attachment ID and URL, or WP_Error on failure.
 */
function upload_image_from_file($file, $alt, $title, $caption, $description) {
    // Step 1: Handle the uploaded file
    $uploaded_file = wp_handle_upload($file, ['test_form' => false]);

    if (isset($uploaded_file['error'])) {
        return new WP_Error('upload_error', $uploaded_file['error']);
    }

    // Step 2: Get the file type and check if it's an image
    $file_info = wp_check_filetype(basename($uploaded_file['file']));
    $mime_type = $file_info['type'];

    if (strpos($mime_type, 'image') === false) {
        @unlink($uploaded_file['file']); // Clean up the temporary file
        return new WP_Error('invalid_image', 'The provided file is not a valid image.');
    }

    // Step 3: Convert the image to WebP
    $image_editor = wp_get_image_editor($uploaded_file['file']);

    if (is_wp_error($image_editor)) {
        @unlink($uploaded_file['file']); // Clean up the temporary file
        return $image_editor;
    }

    // Set compression quality
    $compression_quality = 30; // Adjust this value (1-100) to control compression level
    $image_editor->set_quality($compression_quality);

    // Generate the WebP filename without the original extension
    $filename_without_extension = pathinfo($uploaded_file['file'], PATHINFO_FILENAME);
    $webp_filename = $filename_without_extension . '.webp';

    // Save the WebP file in the uploads directory
    $upload_dir = wp_upload_dir();
    $new_webp_path = $upload_dir['path'] . '/' . $webp_filename;

    $converted = $image_editor->save($new_webp_path, 'image/webp');

    if (is_wp_error($converted)) {
        @unlink($uploaded_file['file']); // Clean up the original file
        return $converted;
    }

    // Step 4: Save the WebP image as a WordPress attachment
    $attachment = array(
        'guid'           => $upload_dir['url'] . '/' . $webp_filename,
        'post_mime_type' => 'image/webp',
        'post_title'     => $title,
        'post_content'   => $description,
        'post_excerpt'   => $caption,
        'post_status'    => 'inherit'
    );

    $attachment_id = wp_insert_attachment($attachment, $new_webp_path);

    if (is_wp_error($attachment_id)) {
        @unlink($new_webp_path); // Clean up the WebP file
        return $attachment_id;
    }

    // Step 5: Generate attachment metadata
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attachment_id, $new_webp_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    // Step 6: Update alt text
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);

    // Clean up temporary files
    @unlink($uploaded_file['file']);

    // Get the file size of the WebP image
    $size = filesize($new_webp_path);

    return [
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment['guid'],
        'size' => $size
    ];
}
function cog_upload_image_from_file() {
    // Check if a file is uploaded
    if (!isset($_FILES['file'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
        return;
    }

    $file = $_FILES['file'];
    $alt = isset($_POST['alt']) ? sanitize_text_field($_POST['alt']) : '';
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $caption = isset($_POST['caption']) ? sanitize_text_field($_POST['caption']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'File upload error']);
        return;
    }

    // Upload file to WordPress media library
    $attachment = upload_image_from_file($file, $alt, $title, $caption, $description);

    if (is_wp_error($attachment)) {
        wp_send_json_error(['message' => $attachment->get_error_message()]);
    } else {
        wp_send_json_success([
            'attachment_id' => $attachment['attachment_id'], 
            'attachment_url' => $attachment['attachment_url'],
            'size' => $attachment['size'],
        ]);
    }
}
add_action('wp_ajax_cog_upload_image_from_file', 'cog_upload_image_from_file');

function cog_fetch_images() {
    if (!isset($_POST['article']) || !isset($_POST['topic'])) {
        wp_send_json_error(['message' => 'Missing required data.']);
    }

    $article_content = sanitize_text_field($_POST['article']);
    $topic = sanitize_text_field($_POST['topic']);
    $settings = get_option('cog_settings');

    $response = chatWithAi(prepareChatText(str_replace(['{{article}}', '{{topic}}'], [$article_content, $topic], $settings['article_images'])));


    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Failed to connect to Google AI.']);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response)) {
        $text = $response;
        wp_send_json_success(['images' => $text]);
    }
}

add_action('wp_ajax_fetch_images', 'cog_fetch_images');
?>