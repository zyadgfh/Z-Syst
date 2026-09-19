import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "jsr:@supabase/supabase-js@2";

const corsHeaders={"Access-Control-Allow-Origin":"*","Access-Control-Allow-Headers":"authorization, x-client-info, apikey, content-type","Access-Control-Allow-Methods":"POST, OPTIONS"};
const rpcMap:Record<string,string>={post_sale:"api_post_sale_financial",receive_purchase:"api_receive_purchase_financial",post_sale_return:"api_post_sale_return_financial",post_purchase_return:"api_post_purchase_return_financial",complete_stock_transfer:"api_complete_stock_transfer"};

Deno.serve(async(req:Request)=>{
 if(req.method==="OPTIONS")return new Response("ok",{headers:corsHeaders});
 try{
  if(req.method!=="POST")throw new Error("POST required");
  const authHeader=req.headers.get("Authorization");
  if(!authHeader?.startsWith("Bearer "))throw new Error("Authorization required");
  const url=Deno.env.get("SUPABASE_URL"),key=Deno.env.get("SUPABASE_ANON_KEY")??Deno.env.get("SUPABASE_PUBLISHABLE_KEY");
  if(!url||!key)throw new Error("Supabase runtime configuration missing");
  const token=authHeader.slice(7);
  const supabase=createClient(url,key,{global:{headers:{Authorization:authHeader}},auth:{persistSession:false,autoRefreshToken:false}});
  const {data:{user},error:userError}=await supabase.auth.getUser(token);
  if(userError||!user)throw new Error("Invalid authentication token");
  const body=await req.json(),rpc=rpcMap[body?.action];
  if(!rpc)throw new Error("Unsupported action");
  const {data,error}=await supabase.rpc(rpc,body?.payload??{});
  if(error)throw error;
  return new Response(JSON.stringify({ok:true,user_id:user.id,action:body.action,data}),{status:200,headers:{...corsHeaders,"Content-Type":"application/json"}});
 }catch(error){
  const message=error instanceof Error?error.message:"Unexpected error";
  return new Response(JSON.stringify({ok:false,error:message}),{status:400,headers:{...corsHeaders,"Content-Type":"application/json"}});
 }
});